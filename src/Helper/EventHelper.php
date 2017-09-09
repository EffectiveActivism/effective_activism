<?php

namespace Drupal\effective_activism\Helper;

/**
 * Helper functions for events.
 */
class EventHelper {

  const DATE_STEP = 60;
  const TIME_FORMAT = 'H:i';

  /**
   * Callback for setting date format.
   *
   * @param array $element
   *   The element array to alter.
   *
   * @return array
   *   The altered element array.
   */
  public static function setDateFormat(array $element) {
    // Remove seconds from time fields.
    $element['time']['#attributes']['step'] = self::DATE_STEP;
    $element['time']['#attributes']['data-time-format'] = self::TIME_FORMAT;
    $pieces = explode(':', $element['time']['#value']);
    if (count($pieces) > 2) {
      $element['time']['#value'] = sprintf('%s:%s', $pieces[0], $pieces[1]);
    }
    return $element;
  }

}
