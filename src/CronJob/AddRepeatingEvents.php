<?php

namespace Drupal\effective_activism\CronJob;

use Drupal;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Helper\EventRepeaterHelper;

/**
 * This cron job adds third-party content entities to events.
 *
 * Adds a batch of third-party content entities every time cron is run.
 */
class AddRepeatingEvents implements CronJobInterface {

  const BATCH_SIZE = 10;

  const FUTURE_EVENTS_LIMIT = 10;

  /**
   * {@inheritdoc}
   */
  public static function run() {
    $event_repeaters = EventRepeaterHelper::getEventRepeaters(self::BATCH_SIZE);
    $events_created = 0;
    foreach ($event_repeaters as $event_repeater) {
      $future_events = $event_repeater->getFutureEvents();
      if (
        $event_repeater->isActive() &&
        count($future_events) < self::FUTURE_EVENTS_LIMIT
      ) {
        // Create up to FUTURE_EVENTS_LIMIT number of events.
        while ($future_events < self::FUTURE_EVENTS_LIMIT) {
          $events_created++;
          $future_events = $event_repeater->getFutureEvents();
        }
        
      }
    }
    if ($events_created > 0) {
      Drupal::logger('effective_activism')->info(sprintf('%d repeated event(s) created', $events_created));
    }
  }

}
