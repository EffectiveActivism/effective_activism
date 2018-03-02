<?php

namespace Drupal\effective_activism\Helper;

use Drupal;
use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Entity\EventTemplate;

/**
 * Helper functions for querying events through filter.
 */
class EventTemplateHelper {

  /**
   * Applies event template to event form.
   *
   * @param \Drupal\effective_activism\Entity\EventTemplate $event_template
   *   The event template to use.
   * @param array $event_form
   *   A fresh event form.
   *
   * @return array
   *   The event form with default values set.
   */
  public static function applyEventTemplate(EventTemplate $event_template, array $event_form) {
    $event_form['title']['widget'][0]['value']['#default_value'] = $event_template->event_title->value;
    $event_form['description']['widget'][0]['value']['#default_value'] = $event_template->event_description->value;
    return $event_form;
  }

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
