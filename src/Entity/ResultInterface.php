<?php

namespace Drupal\effective_activism\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Result entities.
 *
 * @ingroup effective_activism
 */
interface ResultInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Result type.
   *
   * @return string
   *   The Result type.
   */
  public function getType();

  /**
   * Gets the Result creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Result.
   */
  public function getCreatedTime();

  /**
   * Sets the Result creation timestamp.
   *
   * @param int $timestamp
   *   The Result creation timestamp.
   *
   * @return \Drupal\effective_activism\Entity\ResultInterface
   *   The called Result entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Result published status indicator.
   *
   * Unpublished Result are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Result is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Result.
   *
   * @param bool $published
   *   TRUE to set this Result to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\effective_activism\Entity\ResultInterface
   *   The called Result entity.
   */
  public function setPublished($published);

}
