<?php

namespace Drupal\effective_activism\Helper\LocationCoordinates;

use Drupal\Core\TypedData\TypedData;

/**
 * The latitude of an address.
 */
class Latitude extends TypedData {

  /**
   * Cached value.
   *
   * @var float|null
   */
  protected $latitude = NULL;

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return $this->latitude;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->latitude = $value;
    // Notify the parent of any changes.
    if ($notify & isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}
