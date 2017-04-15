<?php

namespace Drupal\effective_activism\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Organization entities.
 *
 * @ingroup effective_activism
 */
interface OrganizationInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Organization creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Organization.
   */
  public function getCreatedTime();

  /**
   * Sets the Organization creation timestamp.
   *
   * @param int $timestamp
   *   The Organization creation timestamp.
   *
   * @return \Drupal\effective_activism\Entity\OrganizationInterface
   *   The called Organization entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Organization published status indicator.
   *
   * Unpublished Organization are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Organization is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Organization.
   *
   * @param bool $published
   *   TRUE to publish Organization, FALSE to unpublish.
   *
   * @return \Drupal\effective_activism\Entity\OrganizationInterface
   *   The called Organization entity.
   */
  public function setPublished($published);

}
