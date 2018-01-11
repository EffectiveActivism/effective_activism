<?php

namespace Drupal\effective_activism\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Event template entities.
 *
 * @ingroup effective_activism
 */
interface EventTemplateInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Event template name.
   *
   * @return string
   *   Name of the Event template.
   */
  public function getName();

  /**
   * Sets the Event template name.
   *
   * @param string $name
   *   The Event template name.
   *
   * @return \Drupal\effective_activism\Entity\EventTemplateInterface
   *   The called Event template entity.
   */
  public function setName($name);

  /**
   * Gets the Event template creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Event template.
   */
  public function getCreatedTime();

  /**
   * Sets the Event template creation timestamp.
   *
   * @param int $timestamp
   *   The Event template creation timestamp.
   *
   * @return \Drupal\effective_activism\Entity\EventTemplateInterface
   *   The called Event template entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Event template published status indicator.
   *
   * Unpublished Event template are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Event template is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Event template.
   *
   * @param bool $published
   *   TRUE to set this Event template to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\effective_activism\Entity\EventTemplateInterface
   *   The called Event template entity.
   */
  public function setPublished($published);

  /**
   * Gets the Event template revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Event template revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\effective_activism\Entity\EventTemplateInterface
   *   The called Event template entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Event template revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Event template revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\effective_activism\Entity\EventTemplateInterface
   *   The called Event template entity.
   */
  public function setRevisionUserId($uid);

}
