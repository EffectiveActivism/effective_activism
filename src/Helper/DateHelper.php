<?php

namespace Drupal\effective_activism\Helper;

use Drupal;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Constant;

/**
 * Helper functions for event dates.
 */
class DateHelper {

  /**
   * Returns now relative to organization or group timezone.
   *
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization.
   * @param \Drupal\effective_activism\Entity\Group $group
   *   The group.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   A DrupalDateTime object of 'now' relative to the proper timezone.
   */
  public static function getNow(Organization $organization, Group $group = NULL) {
    $timezone = (!empty($group) && $group->timezone->value !== Constant::GROUP_INHERIT_TIMEZONE) ? $group->timezone->value : $organization->timezone->value;
    return new DrupalDateTime('now', $timezone);
  }

}
