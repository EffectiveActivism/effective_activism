<?php

namespace Drupal\effective_activism\Helper;

use DateTimeZone;
use Drupal;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Entity\EventTemplate;
use Drupal\effective_activism\Plugin\Field\FieldWidget\DateTimePickerWidget;

/**
 * Helper functions for querying events through filter.
 */
class EventTemplateHelper {

  /**
   * Get events that use a certain template.
   *
   * @param \Drupal\effective_activism\Entity\EventTemplate $event_template
   *   The event template to get events from.
   * @param int $position
   *   The position to start from.
   * @param int $limit
   *   The number of events to return.
   * @param bool $load_entities
   *   Wether to return full entity objects or entity ids.
   *
   * @return array
   *   An array of events related to the group.
   */
  public static function getEvents(EventTemplate $event_template, $position = 0, $limit = 0, $load_entities = TRUE) {
    $query = Drupal::entityQuery('event')
      ->condition('event_template', $event_template->id())
      ->sort('start_date');
    if ($limit > 0) {
      $query->range($position, $limit + $position);
    }
    $result = $query->execute();
    return $load_entities ? Event::loadMultiple($result) : array_values($result);
  }

}
