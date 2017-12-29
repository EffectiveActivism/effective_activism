<?php

namespace Drupal\effective_activism\Helper;

use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Entity\Filter;

/**
 * Helper functions for querying events through filter.
 */
class FilterHelper {

  /**
   * Get events matching filter.
   *
   * @param \Drupal\effective_activism\Entity\Filter $filter
   *   The filter to get matching events from.
   * @param int $position
   *   The position to start from.
   * @param int $limit
   *   The number of events to return.
   * @param bool $load_entities
   *   Wether to return full entity objects or entity ids.
   *
   * @return array
   *   An array of events that matches the filter.
   */
  public static function getEvents(Filter $filter, $position = 0, $limit = 0, $load_entities = TRUE) {
    $group_ids = OrganizationHelper::getGroups($filter->organization->entity, 0, 0, FALSE);
    $query = \Drupal::entityQuery('event')
      ->condition('parent', $group_ids, 'IN')
      ->sort('start_date');
    if ($limit > 0) {
      $query->range($position, $limit + $position);
    }
    $result = $query->execute();
    return $load_entities ? Event::loadMultiple($result) : array_values($result);
  }

  /**
   * Get events matching filter paged for use in overviews.
   *
   * @param \Drupal\effective_activism\Entity\Filter $filter
   *   The filter to get matching events from.
   * @param int $page_count
   *   How many entities to include.
   * @param bool $load_entities
   *   Wether to return full entity objects or entity ids.
   *
   * @return array
   *   An array of events that matches the filter.
   */
  public static function getEventsPaged(Filter $filter, $page_count = 20, $load_entities = TRUE) {
    $group_ids = OrganizationHelper::getGroups($filter->organization->entity, 0, 0, FALSE);
    $query = \Drupal::entityQuery('event')
      ->condition('parent', $group_ids, 'IN')
      ->pager($page_count)
      ->sort('start_date');
    $result = $query->execute();
    return $load_entities ? Event::loadMultiple($result) : array_values($result);
  }

}
