<?php

namespace Drupal\effective_activism\Helper;

use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Import;

/**
 * Helper functions for querying groups.
 */
class GroupHelper {

  /**
   * Get group events.
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
  public static function getEvents(Group $group, $position = 0, $limit = 0, $load_entities = TRUE) {
    $query = \Drupal::entityQuery('event')
      ->condition('parent', $group->id());
    if ($limit > 0) {
      $query->range($position, $limit + $position);
    }
    $result = $query->execute();
    return $load_entities ? Event::loadMultiple($result) : array_values($result);
  }

  /**
   * Get group imports.
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
  public static function getImports(Group $group, $position = 0, $limit = 0, $load_entities = TRUE) {
    $query = \Drupal::entityQuery('import')
      ->condition('parent', $group->id());
    if ($limit > 0) {
      $query->range($position, $limit + $position);
    }
    $result = $query->execute();
    return $load_entities ? Import::loadMultiple($result) : array_values($result);
  }
}
