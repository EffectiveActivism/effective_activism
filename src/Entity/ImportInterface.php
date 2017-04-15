<?php

namespace Drupal\effective_activism\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Import entities.
 *
 * @ingroup effective_activism
 */
interface ImportInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Import type.
   *
   * @return string
   *   The Import type.
   */
  public function getType();

  /**
   * Gets the Import creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Import.
   */
  public function getCreatedTime();

  /**
   * Sets the Import creation timestamp.
   *
   * @param int $timestamp
   *   The Import creation timestamp.
   *
   * @return \Drupal\effective_activism\Entity\ImportInterface
   *   The called Import entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Import published status indicator.
   *
   * Unpublished Import are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Import is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Import.
   *
   * @param bool $published
   *   TRUE to set this Import to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\effective_activism\Entity\ImportInterface
   *   The called Import entity.
   */
  public function setPublished($published);

}
