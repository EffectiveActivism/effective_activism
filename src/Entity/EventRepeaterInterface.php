<?php

namespace Drupal\effective_activism\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining EventRepeater entities.
 *
 * @ingroup effective_activism
 */
interface EventRepeaterInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Returns TRUE if event repeater is enabled, FALSE otherwise.
   *
   * @return bool
   *   TRUE if enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Returns a list of upcoming events, relative to the DrupalDateTime 'now'.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $now
   *   Now, relative to a group or organization.
   *
   * @return array
   *   An array of entity ids as integers.
   */
  public function getUpcomingEvents(DrupalDateTime $now);

  /**
   * Schedules and reschedules events and deletes superfluous events.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $now
   *   Now, relative to a group or organization.
   */
  public function scheduleUpcomingEvents(DrupalDateTime $now);

  /**
   * Gets the EventRepeater creation timestamp.
   *
   * @return int
   *   Creation timestamp of the EventRepeater.
   */
  public function getCreatedTime();

  /**
   * Sets the EventRepeater creation timestamp.
   *
   * @param int $timestamp
   *   The EventRepeater creation timestamp.
   *
   * @return \Drupal\effective_activism\Entity\EventRepeaterInterface
   *   The called EventRepeater entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the EventRepeater published status indicator.
   *
   * Unpublished EventRepeater are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the EventRepeater is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a EventRepeater.
   *
   * @param bool $published
   *   TRUE to set this EventRepeater to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\effective_activism\Entity\EventRepeaterInterface
   *   The called EventRepeater entity.
   */
  public function setPublished($published);

  /**
   * Gets the EventRepeater revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the EventRepeater revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\effective_activism\Entity\EventRepeaterInterface
   *   The called EventRepeater entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the EventRepeater revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the EventRepeater revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\effective_activism\Entity\EventRepeaterInterface
   *   The called EventRepeater entity.
   */
  public function setRevisionUserId($uid);

}
