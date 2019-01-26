<?php

namespace Drupal\effective_activism\Helper;

use DateTimeZone;
use Drupal;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Entity\Filter;
use Drupal\effective_activism\Entity\Group;

/**
 * Helper functions for querying events through filter.
 */
class FilterHelper {

  // https://en.wikipedia.org/wiki/Earth_radius.
  const EARTH_RADIUS = 6371000;

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
    $query = Drupal::entityQuery('event')
      ->condition('parent', $group_ids, 'IN')
      ->sort('start_date');
    // Filter by start date.
    if (!$filter->start_date->isEmpty()) {
      $start_date = new DrupalDateTime($filter->start_date->value, new DateTimezone(DATETIME_STORAGE_TIMEZONE));
      $query->condition('start_date', $start_date->format(DATETIME_DATETIME_STORAGE_FORMAT), '>=');
    }
    // Filter by end date.
    if (!$filter->end_date->isEmpty()) {
      $end_date = new DrupalDateTime($filter->end_date->value, new DateTimezone(DATETIME_STORAGE_TIMEZONE));
      $query->condition('end_date', $end_date->format(DATETIME_DATETIME_STORAGE_FORMAT), '<=');
    }
    // Filter by location precision.
    if (!$filter->location->isEmpty() && !$filter->location_precision->isEmpty() && $filter->location_precision->getValue() !== '0') {
      // Distance in meters from center.
      $precision = $filter->location_precision->getValue();
      $distance = (int) $precision[0]['value'] * 1000;
      // Calculate bounding box.
      $offset_latitude = $distance / self::EARTH_RADIUS;
      $offset_longitude = $distance / (self::EARTH_RADIUS * cos(pi() * $filter->location->latitude / 180));
      $bounding_box_latitude_north = $filter->location->latitude + $offset_latitude * 180 / pi();
      $bounding_box_latitude_south = $filter->location->latitude - $offset_latitude * 180 / pi();
      $bounding_box_longitude_west = $filter->location->longitude - $offset_longitude * 180 / pi();
      $bounding_box_longitude_east = $filter->location->longitude + $offset_longitude * 180 / pi();
      $query
        ->condition('location__latitude', $bounding_box_latitude_north, '<')
        ->condition('location__latitude', $bounding_box_latitude_south, '>')
        ->condition('location__longitude', $bounding_box_longitude_east, '<')
        ->condition('location__longitude', $bounding_box_longitude_west, '>');
    }
    // Filter by location.
    elseif (!$filter->location->isEmpty()) {
      if (!empty($filter->location->address)) {
        $query->condition('location__address', $filter->location->address, '=');
      }
      if (!empty($filter->location->extra_information)) {
        $query->condition('location__extra_information', $filter->location->extra_information, 'CONTAINS');
      }
    }
    // Filter by event template.
    if (!$filter->event_templates->isEmpty()) {
      $event_templates = (array_map(function ($element) {
        return $element['target_id'];
      }, $filter->event_templates->getValue()));
      $query->condition('event_template', $event_templates, 'IN');
    }
    // Filter by result type.
    if (!$filter->result_types->isEmpty()) {
      $result_types = (array_map(function ($element) {
        return $element['target_id'];
      }, $filter->result_types->getValue()));
      $query->condition('results.entity.type', $result_types, 'IN');
    }
    if ($limit > 0) {
      $query->range($position, $limit + $position);
    }
    $result = $query->execute();
    return $load_entities ? Event::loadMultiple($result) : array_values($result);
  }

  /**
   * Get events matching filter.
   *
   * @param \Drupal\effective_activism\Entity\Filter $filter
   *   The filter to get matching events from.
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
   *   An array of events that matches the filter.
   */
  public static function getEventsByGroup(Filter $filter, Group $group, $position = 0, $limit = 0, $load_entities = TRUE) {
    $query = Drupal::entityQuery('event')
      ->condition('parent', $group->id())
      ->sort('start_date');
    // Filter by start date.
    if (!$filter->start_date->isEmpty()) {
      $start_date = new DrupalDateTime($filter->start_date->value, new DateTimezone(DATETIME_STORAGE_TIMEZONE));
      $query->condition('start_date', $start_date->format(DATETIME_DATETIME_STORAGE_FORMAT), '>=');
    }
    // Filter by end date.
    if (!$filter->end_date->isEmpty()) {
      $end_date = new DrupalDateTime($filter->end_date->value, new DateTimezone(DATETIME_STORAGE_TIMEZONE));
      $query->condition('end_date', $end_date->format(DATETIME_DATETIME_STORAGE_FORMAT), '<=');
    }
    // Filter by location precision.
    if (!$filter->location->isEmpty() && !$filter->location_precision->isEmpty() && $filter->location_precision->getValue() !== '0') {
      // Distance in meters from center.
      $precision = $filter->location_precision->getValue();
      $distance = (int) $precision[0]['value'] * 1000;
      // Calculate bounding box.
      $offset_latitude = $distance / self::EARTH_RADIUS;
      $offset_longitude = $distance / (self::EARTH_RADIUS * cos(pi() * $filter->location->latitude / 180));
      $bounding_box_latitude_north = $filter->location->latitude + $offset_latitude * 180 / pi();
      $bounding_box_latitude_south = $filter->location->latitude - $offset_latitude * 180 / pi();
      $bounding_box_longitude_west = $filter->location->longitude - $offset_longitude * 180 / pi();
      $bounding_box_longitude_east = $filter->location->longitude + $offset_longitude * 180 / pi();
      $query
        ->condition('location__latitude', $bounding_box_latitude_north, '<')
        ->condition('location__latitude', $bounding_box_latitude_south, '>')
        ->condition('location__longitude', $bounding_box_longitude_east, '<')
        ->condition('location__longitude', $bounding_box_longitude_west, '>');
    }
    // Filter by location.
    elseif (!$filter->location->isEmpty()) {
      if (!empty($filter->location->address)) {
        $query->condition('location__address', $filter->location->address, '=');
      }
      if (!empty($filter->location->extra_information)) {
        $query->condition('location__extra_information', $filter->location->extra_information, 'CONTAINS');
      }
    }
    // Filter by event templates.
    if (!$filter->event_templates->isEmpty()) {
      $event_templates = (array_map(function ($element) {
        return $element['target_id'];
      }, $filter->event_templates->getValue()));
      $query->condition('event_template', $event_templates, 'IN');
    }
    // Filter by result type.
    if (!$filter->result_types->isEmpty()) {
      $result_types = (array_map(function ($element) {
        return $element['target_id'];
      }, $filter->result_types->getValue()));
      $query->condition('results.entity.type', $result_types, 'IN');
    }
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
    $query = Drupal::entityQuery('event')
      ->condition('parent', $group_ids, 'IN')
      ->pager($page_count)
      ->sort('start_date');
    if (!$filter->start_date->isEmpty()) {
      $start_date = new DrupalDateTime($filter->start_date->value, new DateTimezone(DATETIME_STORAGE_TIMEZONE));
      $query->condition('start_date', $start_date->format(DATETIME_DATETIME_STORAGE_FORMAT), '>=');
    }
    if (!$filter->location->isEmpty()) {
      if (!empty($filter->location->address)) {
        $query->condition('location__address', $filter->location->address, '=');
      }
      if (!empty($filter->location->extra_information)) {
        $query->condition('location__extra_information', $filter->location->extra_information, 'CONTAINS');
      }
    }
    if (!$filter->event_templates->isEmpty()) {
      $event_templates = (array_map(function ($element) {
        return $element['target_id'];
      }, $filter->event_templates->getValue()));
      $query->condition('event_template', $event_templates, 'IN');
    }
    $result = $query->execute();
    return $load_entities ? Event::loadMultiple($result) : array_values($result);
  }

}
