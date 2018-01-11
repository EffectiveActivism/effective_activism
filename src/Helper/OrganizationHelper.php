<?php

namespace Drupal\effective_activism\Helper;

use Drupal;
use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Entity\EventTemplate;
use Drupal\effective_activism\Entity\Export;
use Drupal\effective_activism\Entity\Filter;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\ResultType;
use Drupal\effective_activism\Entity\Organization;

/**
 * Helper functions for Organization entities.
 */
class OrganizationHelper {

  /**
   * Get organization event templates.
   *
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization to get event templates from.
   * @param int $position
   *   The position to start from.
   * @param int $limit
   *   The number of event templates to return.
   * @param bool $load_entities
   *   Wether to return full entity objects or entity ids.
   *
   * @return array
   *   An array of event templates related to the organization.
   */
  public static function getEventTemplates(Organization $organization, $position = 0, $limit = 0, $load_entities = TRUE) {
    $query = \Drupal::entityQuery('event_template')
      ->condition('organization', $organization->id())
      ->sort('created');
    if ($limit > 0) {
      $query->range($position, $limit + $position);
    }
    $result = $query->execute();
    return $load_entities ? EventTemplate::loadMultiple($result) : array_values($result);
  }

  /**
   * Get paged organization event templates.
   *
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization to get event templates from.
   * @param int $page_count
   *   How many entities to include.
   * @param bool $load_entities
   *   Wether to return full entity objects or entity ids.
   *
   * @return array
   *   An array of event templates related to the organization.
   */
  public static function getEventTemplatesPaged(Organization $organization, $page_count = 20, $load_entities = TRUE) {
    $query = \Drupal::entityQuery('event_template')
      ->condition('organization', $organization->id())
      ->pager($page_count)
      ->sort('created');
    $result = $query->execute();
    return $load_entities ? EventTemplate::loadMultiple($result) : array_values($result);
  }

  /**
   * Get organization exports.
   *
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization to get exports from.
   * @param int $position
   *   The position to start from.
   * @param int $limit
   *   The number of exports to return.
   * @param bool $load_entities
   *   Wether to return full entity objects or entity ids.
   *
   * @return array
   *   An array of exports related to the organization.
   */
  public static function getExports(Organization $organization, $position = 0, $limit = 0, $load_entities = TRUE) {
    $query = \Drupal::entityQuery('export')
      ->condition('organization', $organization->id())
      ->sort('created');
    if ($limit > 0) {
      $query->range($position, $limit + $position);
    }
    $result = $query->execute();
    return $load_entities ? Export::loadMultiple($result) : array_values($result);
  }

  /**
   * Get paged organization exports.
   *
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization to get exports from.
   * @param int $page_count
   *   How many entities to include.
   * @param bool $load_entities
   *   Wether to return full entity objects or entity ids.
   *
   * @return array
   *   An array of exports related to the organization.
   */
  public static function getExportsPaged(Organization $organization, $page_count = 20, $load_entities = TRUE) {
    $query = \Drupal::entityQuery('export')
      ->condition('organization', $organization->id())
      ->pager($page_count)
      ->sort('created');
    $result = $query->execute();
    return $load_entities ? Export::loadMultiple($result) : array_values($result);
  }

  /**
   * Get organization filters.
   *
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization to get filters from.
   * @param int $position
   *   The position to start from.
   * @param int $limit
   *   The number of filters to return.
   * @param bool $load_entities
   *   Wether to return full entity objects or entity ids.
   *
   * @return array
   *   An array of filters related to the organization.
   */
  public static function getFilters(Organization $organization, $position = 0, $limit = 0, $load_entities = TRUE) {
    $query = \Drupal::entityQuery('filter')
      ->condition('organization', $organization->id())
      ->sort('created');
    if ($limit > 0) {
      $query->range($position, $limit + $position);
    }
    $result = $query->execute();
    return $load_entities ? Filter::loadMultiple($result) : array_values($result);
  }

  /**
   * Get paged organization filters.
   *
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization to get filters from.
   * @param int $page_count
   *   How many entities to include.
   * @param bool $load_entities
   *   Wether to return full entity objects or entity ids.
   *
   * @return array
   *   An array of filters related to the organization.
   */
  public static function getFiltersPaged(Organization $organization, $page_count = 20, $load_entities = TRUE) {
    $query = \Drupal::entityQuery('filter')
      ->condition('organization', $organization->id())
      ->pager($page_count)
      ->sort('created');
    $result = $query->execute();
    return $load_entities ? Filter::loadMultiple($result) : array_values($result);
  }

  /**
   * Get organization groups.
   *
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization to get groups from.
   * @param int $position
   *   The position to start from.
   * @param int $limit
   *   The number of organizations to return.
   * @param bool $load_entities
   *   Wether to return full entity objects or entity ids.
   *
   * @return array
   *   An array of groups related to the organization.
   */
  public static function getGroups(Organization $organization, $position = 0, $limit = 0, $load_entities = TRUE) {
    $query = Drupal::entityQuery('group')
      ->condition('organization', $organization->id())
      ->sort('title');
    if ($limit > 0) {
      $query->range($position, $limit + $position);
    }
    $result = $query->execute();
    return $load_entities ? Group::loadMultiple($result) : array_values($result);
  }

  /**
   * Get organization groups paged for use in overviews.
   *
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization to get groups from.
   * @param int $page_count
   *   How many entities to include.
   * @param bool $load_entities
   *   Wether to return full entity objects or entity ids.
   *
   * @return array
   *   An array of groups related to the organization.
   */
  public static function getGroupsPaged(Organization $organization, $page_count = 20, $load_entities = TRUE) {
    $query = Drupal::entityQuery('group')
      ->condition('organization', $organization->id())
      ->pager($page_count)
      ->sort('title');
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
   * @param bool $load_entities
   *   Wether to return full entity objects or entity ids.
   *
   * @return array
   *   An array of organizations.
   */
  public static function getOrganizations($position = 0, $limit = 0, $load_entities = TRUE) {
    $query = Drupal::entityQuery('organization')
      ->sort('title');
    if ($limit > 0) {
      $query->range($position, $limit + $position);
    }
    $result = $query->execute();
    return $load_entities ? Organization::loadMultiple($result) : array_values($result);
  }

  /**
   * Get events of all groups of the organization.
   *
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization to get events from.
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
  public static function getEvents(Organization $organization, $position = 0, $limit = 0, $load_entities = TRUE) {
    $result = [];
    $groups = self::getGroups($organization, 0, 0, FALSE);
    if (!empty($groups)) {
      $query = Drupal::entityQuery('event')
        ->condition('parent', $groups, 'IN')
        ->sort('start_date');
      if ($limit > 0) {
        $query->range($position, $limit + $position);
      }
      $result = $query->execute();
    }
    return $load_entities ? Event::loadMultiple($result) : array_values($result);
  }

  /**
   * Get all result types belonging to an organizations.
   *
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization to get result types for.
   * @param bool $load_entities
   *   Wether to return full entity objects or entity ids.
   *
   * @return array
   *   An array of result types.
   */
  public static function getResultTypes(Organization $organization, $load_entities = TRUE) {
    $query = Drupal::entityQuery('result_type')
      ->condition('organization', $organization->id())
      ->sort('organization');
    $result = $query->execute();
    return $load_entities ? ResultType::loadMultiple($result) : array_values($result);
  }

}
