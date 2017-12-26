<?php

namespace Drupal\effective_activism\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Filter entities.
 *
 * @ingroup effective_activism
 */
interface FilterInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Filter name.
   *
   * @return string
   *   Name of the Filter.
   */
  public function getName();

  /**
   * Sets the Filter name.
   *
   * @param string $name
   *   The Filter name.
   *
   * @return \Drupal\effective_activism\Entity\FilterInterface
   *   The called Filter entity.
   */
  public function setName($name);

  /**
   * Gets the Filter creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Filter.
   */
  public function getCreatedTime();

  /**
   * Sets the Filter creation timestamp.
   *
   * @param int $timestamp
   *   The Filter creation timestamp.
   *
   * @return \Drupal\effective_activism\Entity\FilterInterface
   *   The called Filter entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Filter published status indicator.
   *
   * Unpublished Filter are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Filter is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Filter.
   *
   * @param bool $published
   *   TRUE to set this Filter to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\effective_activism\Entity\FilterInterface
   *   The called Filter entity.
   */
  public function setPublished($published);

  /**
   * Gets the Filter revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Filter revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\effective_activism\Entity\FilterInterface
   *   The called Filter entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Filter revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Filter revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\effective_activism\Entity\FilterInterface
   *   The called Filter entity.
   */
  public function setRevisionUserId($uid);

}
