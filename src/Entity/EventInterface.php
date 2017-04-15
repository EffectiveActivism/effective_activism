<?php

namespace Drupal\effective_activism\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Event entities.
 *
 * @ingroup effective_activism
 */
interface EventInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Event creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Event.
   */
  public function getCreatedTime();

  /**
   * Sets the Event creation timestamp.
   *
   * @param int $timestamp
   *   The Event creation timestamp.
   *
   * @return \Drupal\effective_activism\Entity\EventInterface
   *   The called Event entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Event published status indicator.
   *
   * Unpublished Event are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Event is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Event.
   *
   * @param bool $published
   *   TRUE to set this Event to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\effective_activism\Entity\EventInterface
   *   The called Event entity.
   */
  public function setPublished($published);

}
