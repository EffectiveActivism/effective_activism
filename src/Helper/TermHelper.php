<?php

namespace Drupal\effective_activism\Helper;

use Drupal;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Entity\Event;
use Drupal\taxonomy\Entity\Term;

/**
 * Helper functions for querying terms.
 */
class TermHelper {

  /**
   * Get group events.
   *
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization to get events from.
   * @param \Drupal\taxonomy\Entity\Term $term
   *   The term to get events from.
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
  public static function getEvents(Organization $organization, Term $term, $position = 0, $limit = 0, $load_entities = TRUE) {
    $query = Drupal::entityQuery('result')
      ->exists(sprintf('tags_%d', $organization->id()))
      ->condition(sprintf('tags_%d', $organization->id()), $term->id());
    $result = $query->execute();
    $query = Drupal::entityQuery('event')
      ->condition('results', array_keys($result), 'IN')
      ->sort('created');
    if ($limit > 0) {
      $query->range($position, $limit + $position);
    }
    $result = $query->execute();
    return $load_entities ? Event::loadMultiple($result) : array_values($result);
  }

}
