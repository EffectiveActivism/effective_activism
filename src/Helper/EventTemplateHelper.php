<?php

namespace Drupal\effective_activism\Helper;

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

}
