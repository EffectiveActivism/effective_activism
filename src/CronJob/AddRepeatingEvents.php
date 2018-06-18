<?php

namespace Drupal\effective_activism\CronJob;

use Drupal;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Helper\DateHelper;

/**
 * This cron job adds third-party content entities to events.
 *
 * Adds a batch of third-party content entities every time cron is run.
 */
class AddRepeatingEvents implements CronJobInterface {

  const BATCH_SIZE = 100;

  /**
   * {@inheritdoc}
   */
  public static function run()
  {
    $position = Drupal::state()->get('effective_activism_event_repeater_position', 0);
    // Approximate now.
    $now = new DrupalDateTime('now -12 hours');
    // Find all active event repeaters.
    $query = Drupal::entityQuery('event');
    $result = $query
      // Make sure that oldest updated content comes first.
      ->sort('updated', 'ASC')
      ->condition('event_repeater.entity.step', 0, '>')
      ->condition('event_repeater.entity.end_on_date', $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT), '>=')
      ->range($position, $position + self::BATCH_SIZE)
      ->execute();
    $events = Event::loadMultiple($result);
    $processed_event_repeaters = [];
    foreach ($events as $event) {
      if (!in_array($event->event_repeater->entity->id(), $processed_event_repeaters)) {
        $now = DateHelper::getNow(Drupal::request()->get('organization'), Drupal::request()->get('group'));
        $event->event_repeater->entity->scheduleUpcomingEvents($now);
      }
      $processed_event_repeaters[] = $event->event_repeater->entity->id();
    }
    // Reset position if we have gone through the entire batch.
    $position = count($events) === self::BATCH_SIZE ? $position + self::BATCH_SIZE : 0;
    Drupal::state()->set('effective_activism_event_repeater_position', $position);
    Drupal::logger('event_repeater')->info(sprintf('%d event repeaters updated', count($processed_event_repeaters)));
  }

}
