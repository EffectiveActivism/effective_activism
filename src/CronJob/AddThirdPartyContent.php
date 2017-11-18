<?php

namespace Drupal\effective_activism\CronJob;

use Drupal;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Helper\ThirdPartyContentHelper;

/**
 * This cron job adds third-party content entities to events.
 *
 * Adds a batch of third-party content entities every time cron is run.
 */
class AddThirdPartyContent {

  const BATCH_SIZE = 10;

  /**
   * {@inheritdoc}
   */
  public static function run() {
    // Add weather information.
    $event_ids_without_weather_information = ThirdPartyContentHelper::getEventsWithoutThirdPartyContentType(Constant::THIRD_PARTY_CONTENT_TYPE_WEATHER_INFORMATION, self::BATCH_SIZE);
    if (!empty($event_ids_without_weather_information)) {
      foreach ($event_ids_without_weather_information as $id) {
        $event = Event::load($id);
        // Create or get matching third-party content.
        $weather_information = ThirdPartyContentHelper::getThirdPartyContent([
          'type' => Constant::THIRD_PARTY_CONTENT_TYPE_WEATHER_INFORMATION,
          'field_latitude' => $event->get('location')->latitude,
          'field_longitude' => $event->get('location')->longitude,
          'field_timestamp' => $event->get('start_date')->date->format('U'),
        ]);
        if ($weather_information === FALSE) {
          Drupal::logger('effective_activism')->warning(sprintf('Failed to create weather information for event with id %s', $id));
          return;
        }
        // Add entity to event.
        $event->third_party_content[] = [
          'target_id' => $weather_information->id(),
        ];
        $event->setNewRevision();
        $event->save();
      }
      Drupal::logger('effective_activism')->info(sprintf('%d event(s) added weather information', count($event_ids_without_weather_information)));
    }
    // Add demographics.
    $event_ids_without_demographics = ThirdPartyContentHelper::getEventsWithoutThirdPartyContentType(Constant::THIRD_PARTY_CONTENT_TYPE_DEMOGRAPHICS, self::BATCH_SIZE);
    if (!empty($event_ids_without_demographics)) {
      foreach ($event_ids_without_demographics as $id) {
        $event = Event::load($id);
        // Create or get matching third-party content.
        $demographics = ThirdPartyContentHelper::getThirdPartyContent([
          'type' => Constant::THIRD_PARTY_CONTENT_TYPE_DEMOGRAPHICS,
          'field_latitude' => $event->get('location')->latitude,
          'field_longitude' => $event->get('location')->longitude,
        ]);
        if ($demographics === FALSE) {
          Drupal::logger('effective_activism')->warning(sprintf('Failed to create demographics for event with id %s', $id));
          return;
        }
        // Add entity to event.
        $event->third_party_content[] = [
          'target_id' => $demographics->id(),
        ];
        $event->setNewRevision();
        $event->save();
      }
      Drupal::logger('effective_activism')->info(sprintf('%d event(s) added demographics', count($event_ids_without_demographics)));
    }
  }

}
