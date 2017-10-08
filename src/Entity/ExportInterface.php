<?php

namespace Drupal\effective_activism\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Export entities.
 *
 * @ingroup effective_activism
 */
interface ExportInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Export type.
   *
   * @return string
   *   The Export type.
   */
  public function getType();

  /**
   * Gets the Export creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Export.
   */
  public function getCreatedTime();

  /**
   * Sets the Export creation timestamp.
   *
   * @param int $timestamp
   *   The Export creation timestamp.
   *
   * @return \Drupal\effective_activism\Entity\ExportInterface
   *   The called Export entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Export published status indicator.
   *
   * Unpublished Export are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Export is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Export.
   *
   * @param bool $published
   *   TRUE to set this Export to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\effective_activism\Entity\ExportInterface
   *   The called Export entity.
   */
  public function setPublished($published);

}
