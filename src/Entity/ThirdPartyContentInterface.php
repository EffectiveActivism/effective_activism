<?php

namespace Drupal\effective_activism\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining ThirdPartyContent entities.
 *
 * @ingroup effective_activism
 */
interface ThirdPartyContentInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the ThirdPartyContent type.
   *
   * @return string
   *   The ThirdPartyContent type.
   */
  public function getType();

  /**
   * Gets the ThirdPartyContent creation timestamp.
   *
   * @return int
   *   Creation timestamp of the ThirdPartyContent.
   */
  public function getCreatedTime();

  /**
   * Sets the ThirdPartyContent creation timestamp.
   *
   * @param int $timestamp
   *   The Data creation timestamp.
   *
   * @return \Drupal\effective_activism\Entity\ThirdPartyContentInterface
   *   The called ThirdPartyContent entity.
   */
  public function setCreatedTime($timestamp);

}
