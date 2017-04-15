<?php

namespace Drupal\effective_activism\Helper;

use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Organization;

/**
 * Helper functions for Organization entities.
 */
class OrganizationHelper {

  /**
   * Get organization groups.
   *
   * @param Organization $organization
   *   The organization to get groups from.
   * @param int $position
   *   The position to start from.
   * @param int $limit
   *   The number of organizations to return.
   * @param bool $$load_entities
   *   Wether to return full entity objects or entity ids.
   *
   * @return array
   *   An array of groups related to the organization.
   */
  public static function getGroups(Organization $organization, $position = 0, $limit = 0, $load_entities = TRUE) {
    $query = \Drupal::entityQuery('group')
      ->condition('organization', $organization->id());
    if ($limit > 0) {
      $query->range($position, $limit + $position);
    }
    $result = $query->execute();
    return $load_entities ? Group::loadMultiple($result) : array_values($result);
  }

  /**
   * Get all organizations.
   *
   * @param int $position
   *   The position to start from.
   * @param int $limit
   *   The number of organizations to return.
   * @param bool $$load_entities
   *   Wether to return full entity objects or entity ids.
   *
   * @return array
   *   An array of organizations.
   * 
   */
  public static function getOrganizations($position = 0, $limit = 0, $load_entities = TRUE) {
    $query = \Drupal::entityQuery('organization');
    if ($limit > 0) {
      $query->range($position, $limit + $position);
    }
    $result = $query->execute();
    return $load_entities ? Organization::loadMultiple($result) : array_values($result);
  }

  /**
   * Get events of all groups of the organization.
   *
   * @param Group $group
   *   The group to get events from.
   * @param int $position
   *   The position to start from.
   * @param int $limit
   *   The number of events to return.
   * @param bool $$load_entities
   *   Wether to return full entity objects or entity ids.
   *
   * @return array
   *   An array of events related to the group.
   */
  public static function getEvents(Organization $organization, $position = 0, $limit = 0, $load_entities = TRUE) {
    $result = [];
    $groups = self::getGroups($organization, 0, 0, FALSE);
    if (!empty($groups)) {
      $query = \Drupal::entityQuery('event')
        ->condition('parent', $groups, 'IN');
      if ($limit > 0) {
        $query->range($position, $limit + $position);
      }
      $result = $query->execute();
    }
    return $load_entities ? Event::loadMultiple($result) : array_values($result);
  }
}
