<?php

namespace Drupal\effective_activism\Helper;

use DateTime;
use Drupal\effective_activism\Entity\Event;

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
    // Remove seconds in browsers that support HTML5 type=date.
    $element['time']['#attributes']['step'] = self::DATE_STEP;
    $element['time']['#attributes']['data-time-format'] = self::TIME_FORMAT;
    return $element;
  }
}
