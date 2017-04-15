<?php

namespace Drupal\effective_activism\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Group entities.
 *
 * @ingroup effective_activism
 */
interface GroupInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Group creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Group.
   */
  public function getCreatedTime();

  /**
   * Sets the Group creation timestamp.
   *
   * @param int $timestamp
   *   The Group creation timestamp.
   *
   * @return \Drupal\effective_activism\Entity\GroupInterface
   *   The called Group entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Group published status indicator.
   *
   * Unpublished Group are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Group is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Group.
   *
   * @param bool $published
   *   TRUE to set this Group to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\effective_activism\Entity\GroupInterface
   *   The called Group entity.
   */
  public function setPublished($published);

  /**
   * Get siblings of the group.
   *
   * @return array
   *   An array of groups related to this entity, including itself.
   */
  public function getSiblings();

}
