<?php

namespace Drupal\effective_activism\CronJob;

use DateTime;
use DateTimeZone;
use Drupal;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Entity\ThirdPartyContent;
use Drupal\effective_activism\Helper\DateHelper;
use Drupal\effective_activism\ThirdPartyApi\ArcGis;
use Drupal\effective_activism\ThirdPartyApi\DarkSky;
use Drupal\effective_activism\ThirdPartyApi\GDELT;
use Drupal\effective_activism\ThirdPartyApi\GoogleMaps;
use Drupal\effective_activism\ThirdPartyApi\ThirdPartyApiException;

/**
 * This cron job populates existing third party content entities.
 *
 * Processed a batch of third party content entities every time cron is run.
 */
class PopulateThirdPartyContent implements CronJobInterface {

  const BATCH_SIZE = 10;

  const TIME_WINDOW = 60 * 60 * 24;

  /**
   * {@inheritdoc}
   */
  public static function run() {
    // Find third-party content that hasn't been updated.
    $query = Drupal::entityQuery('third_party_content');
    $query
      ->condition('status', 0)
      ->range(0, self::BATCH_SIZE);
    $third_party_content_ids = $query->execute();
    if (!empty($third_party_content_ids)) {
      foreach ($third_party_content_ids as $id) {
        $third_party_content = ThirdPartyContent::load($id);
        // Get the latest event that reference this third-party content.
        $event_query = Drupal::entityQuery('event');
        $event_query
          ->sort('start_date', 'DESC')
          ->condition('third_party_content', $id)
          ->range(0,1);
        $event_ids = $event_query->execute();
        if (!empty($event_ids)) {
          $event = Event::load(array_pop($event_ids));
          $timezone = $event->parent->entity->timezone->value !== Constant::GROUP_INHERIT_TIMEZONE ? $event->parent->entity->timezone->value : $event->parent->entity->organization->entity->timezone->value;
          $start_date = new DateTime($event->start_date->value, new DateTimeZone($timezone));
          $now = DateHelper::getNow($event->parent->entity->organization->entity);
          // Only update third-party content if the event that reference it is
          // older than a day.
          if ($start_date->getTimestamp() < $now->getTimestamp() - self::TIME_WINDOW) {
            $api = NULL;
            try {
              switch ($third_party_content->getType()) {
                case Constant::THIRD_PARTY_CONTENT_TYPE_CITY_PULSE:
                  $api = new GDELT($third_party_content);
                  break;

                case Constant::THIRD_PARTY_CONTENT_TYPE_DEMOGRAPHICS:
                  $api = new ArcGis($third_party_content);
                  break;

                case Constant::THIRD_PARTY_CONTENT_TYPE_EXTENDED_LOCATION_INFORMATION:
                  $api = new GoogleMaps($third_party_content);
                  break;

                case Constant::THIRD_PARTY_CONTENT_TYPE_WEATHER_INFORMATION:
                  $api = new DarkSky($third_party_content);
                  break;

              }
              // Populate entity with API data.
              if (isset($api)) {
                $api->request();
              } else {
                throw new ThirdPartyAPIException('Unknown third-party content bundle');
              }
            }
            catch (ThirdPartyApiException $exception) {
              Drupal::logger('effective_activism')->warning(sprintf('ThirdPartyContent id: %d Message: %s', $third_party_content->id(), $exception->getMessage()));
            }
          }
        }
      }
    }
  }

}
