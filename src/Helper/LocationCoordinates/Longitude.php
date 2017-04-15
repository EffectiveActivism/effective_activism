<?php

namespace Drupal\effective_activism\Helper\LocationCoordinates;

use Drupal\Core\TypedData\TypedData;

/**
 * The longitude of an address.
 */
class Longitude extends TypedData {

  /**
   * Cached value.
   *
   * @var float|null
   */
  protected $latitude = NULL;

  /**
   * {@inheritdoc}
   */
  public function getValue($langcode = NULL) {
    return $this->longitude;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->longitude = $value;
    // Notify the parent of any changes.
    if ($notify & isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}
