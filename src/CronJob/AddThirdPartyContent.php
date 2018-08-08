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
class AddThirdPartyContent implements CronJobInterface {

  const BATCH_SIZE = 10;

  /**
   * {@inheritdoc}
   */
  public static function run() {
    // Get third-party content types.
    $bundles = Drupal::entityManager()->getBundleInfo(Constant::ENTITY_THIRD_PARTY_CONTENT);
    // Add third-party content for each bundle.
    foreach ($bundles as $bundle => $info) {
      $event_ids = ThirdPartyContentHelper::getEventsWithoutThirdPartyContentType($bundle, self::BATCH_SIZE);
      if (!empty($event_ids)) {
        foreach ($event_ids as $id) {
          $event = Event::load($id);
          // Create or get matching third-party content.
          if (in_array($bundle, Constant::THIRD_PARTY_CONTENT_TIME_AWARE)) {
            $third_party_content = ThirdPartyContentHelper::getThirdPartyContent([
              'type' => $bundle,
              'field_latitude' => $event->get('location')->latitude,
              'field_longitude' => $event->get('location')->longitude,
              'field_timestamp' => $event->get('start_date')->date->format('U'),
            ]);
          }
          else {
            $third_party_content = ThirdPartyContentHelper::getThirdPartyContent([
              'type' => $bundle,
              'field_latitude' => $event->get('location')->latitude,
              'field_longitude' => $event->get('location')->longitude,
            ]);
          }
          if ($third_party_content === FALSE) {
            Drupal::logger('effective_activism')->warning(sprintf('Failed to create %s for event with id %d', $bundle, $id));
            return;
          }
          // Add entity to event.
          $event->third_party_content[] = [
            'target_id' => $third_party_content->id(),
          ];
          $event->setNewRevision();
          $event->save();
        }
      }
    }
  }

}
