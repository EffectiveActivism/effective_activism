<?php

namespace Drupal\effective_activism\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Data entities.
 *
 * @ingroup effective_activism
 */
interface DataInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Data type.
   *
   * @return string
   *   The Data type.
   */
  public function getType();

  /**
   * Gets the Data creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Data.
   */
  public function getCreatedTime();

  /**
   * Sets the Data creation timestamp.
   *
   * @param int $timestamp
   *   The Data creation timestamp.
   *
   * @return \Drupal\effective_activism\Entity\DataInterface
   *   The called Data entity.
   */
  public function setCreatedTime($timestamp);

}
