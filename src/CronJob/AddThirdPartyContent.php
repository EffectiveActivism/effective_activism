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
    // Look for events without weather information.
    $query = Drupal::entityQuery('event');
    $group = $query->orConditionGroup()
      ->condition('third_party_content.entity.type', Constant::THIRD_PARTY_CONTENT_TYPE_WEATHER_INFORMATION, '!=')
      ->notExists('third_party_content');
    $query
      ->condition('status', 1)
      ->condition($group)
      ->range(0, self::BATCH_SIZE);
    $event_ids = $query->execute();
    if (!empty($event_ids)) {
      foreach ($event_ids as $id) {
        $event = Event::load($id);
        // Create or get matching third-party content.
        $weather_information = ThirdPartyContentHelper::getThirdPartyContent([
          'type' => Constant::THIRD_PARTY_CONTENT_TYPE_WEATHER_INFORMATION,
          'field_latitude' => $event->get('location')->latitude,
          'field_longitude' => $event->get('location')->longitude,
          'field_timestamp' => $event->get('start_date')->date->format('U'),
        ]);
        // Add entity to event.
        $event->third_party_content[] = [
          'target_id' => $weather_information->id(),
        ];
        $event->setNewRevision();
        $event->save();
      }
    }
  }

}
