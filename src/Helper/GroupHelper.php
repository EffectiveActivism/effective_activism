<?php

namespace Drupal\effective_activism\Helper;

use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Entity\Export;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Import;

/**
 * Helper functions for querying groups.
 */
class GroupHelper {

  /**
   * Get group events.
   *
   * @param \Drupal\effective_activism\Entity\Group $group
   *   The group to get events from.
   * @param int $position
   *   The position to start from.
   * @param int $limit
   *   The number of events to return.
   * @param bool $load_entities
   *   Wether to return full entity objects or entity ids.
   *
   * @return array
   *   An array of events related to the group.
   */
  public static function getEvents(Group $group, $position = 0, $limit = 0, $load_entities = TRUE) {
    $query = \Drupal::entityQuery('event')
      ->condition('parent', $group->id())
      ->sort('start_date');
    if ($limit > 0) {
      $query->range($position, $limit + $position);
    }
    $result = $query->execute();
    return $load_entities ? Event::loadMultiple($result) : array_values($result);
  }

  /**
   * Get group events paged for use in overviews.
   *
   * @param \Drupal\effective_activism\Entity\Group $group
   *   The group to get events from.
   * @param int $page_count
   *   How many entities to include.
   * @param bool $load_entities
   *   Wether to return full entity objects or entity ids.
   *
   * @return array
   *   An array of events related to the group.
   */
  public static function getEventsPaged(Group $group, $page_count = 20, $load_entities = TRUE) {
    $query = \Drupal::entityQuery('event')
      ->condition('parent', $group->id())
      ->pager($page_count)
      ->sort('start_date');
    $result = $query->execute();
    return $load_entities ? Event::loadMultiple($result) : array_values($result);
  }

  /**
   * Get group imports.
   *
   * @param \Drupal\effective_activism\Entity\Group $group
   *   The group to get imports from.
   * @param int $position
   *   The position to start from.
   * @param int $limit
   *   The number of events to return.
   * @param bool $load_entities
   *   Wether to return full entity objects or entity ids.
   *
   * @return array
   *   An array of events related to the group.
   */
  public static function getImports(Group $group, $position = 0, $limit = 0, $load_entities = TRUE) {
    $query = \Drupal::entityQuery('import')
      ->condition('parent', $group->id())
      ->sort('created');
    if ($limit > 0) {
      $query->range($position, $limit + $position);
    }
    $result = $query->execute();
    return $load_entities ? Import::loadMultiple($result) : array_values($result);
  }

  /**
   * Get paged group imports.
   *
   * @param \Drupal\effective_activism\Entity\Group $group
   *   The group to get imports from.
   * @param int $page_count
   *   How many entities to include.
   * @param bool $load_entities
   *   Wether to return full entity objects or entity ids.
   *
   * @return array
   *   An array of events related to the group.
   */
  public static function getImportsPaged(Group $group, $page_count = 20, $load_entities = TRUE) {
    $query = \Drupal::entityQuery('import')
      ->condition('parent', $group->id())
      ->pager($page_count)
      ->sort('created');
    $result = $query->execute();
    return $load_entities ? Import::loadMultiple($result) : array_values($result);
  }

  /**
   * Get group exports.
   *
   * @param \Drupal\effective_activism\Entity\Group $group
   *   The group to get exports from.
   * @param int $position
   *   The position to start from.
   * @param int $limit
   *   The number of events to return.
   * @param bool $load_entities
   *   Wether to return full entity objects or entity ids.
   *
   * @return array
   *   An array of events related to the group.
   */
  public static function getExports(Group $group, $position = 0, $limit = 0, $load_entities = TRUE) {
    $query = \Drupal::entityQuery('export')
      ->condition('parent', $group->id())
      ->sort('created');
    if ($limit > 0) {
      $query->range($position, $limit + $position);
    }
    $result = $query->execute();
    return $load_entities ? Export::loadMultiple($result) : array_values($result);
  }

  /**
   * Get paged group exports.
   *
   * @param \Drupal\effective_activism\Entity\Group $group
   *   The group to get exports from.
   * @param int $page_count
   *   How many entities to include.
   * @param bool $load_entities
   *   Wether to return full entity objects or entity ids.
   *
   * @return array
   *   An array of events related to the group.
   */
  public static function getExportsPaged(Group $group, $page_count = 20, $load_entities = TRUE) {
    $query = \Drupal::entityQuery('export')
      ->condition('parent', $group->id())
      ->pager($page_count)
      ->sort('created');
    $result = $query->execute();
    return $load_entities ? Export::loadMultiple($result) : array_values($result);
  }

}
