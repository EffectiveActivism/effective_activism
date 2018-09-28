<?php

namespace Drupal\effective_activism\Helper;

use Drupal\Core\Url;
use Drupal\effective_activism\Entity\Filter;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Organization;

/**
 * Helper functions for maps.
 */
class MapHelper {

  const HEATMAP_DEFAULT_INTENSITY = 0.4;

  /**
   * Returns a list of events formatted for leaflet.js.
   *
   * @param \Drupal\effective_activism\Entity\Filter $filter
   *   The filter to apply.
   * @param \Drupal\effective_activism\Entity\Group $group
   *   An optional group.
   * @param string $data_type
   *   An optional data type.
   *
   * @return array
   *   An array of places.
   */
  public static function getEventPlaces(Filter $filter, Group $group = NULL, $data_type = NULL) {
    $places = [];
    $events = empty($group) ? FilterHelper::getEvents($filter) : FilterHelper::getEventsByGroup($filter, $group);
    $largest_intensity = 0.0;
    foreach ($events as $event) {
      // Skip group if location is not set.
      if (empty($event->location->latitude)) {
        continue;
      }
      // Add intensity if data type is selected.
      $intensity = self::HEATMAP_DEFAULT_INTENSITY;
      if (!empty($data_type) && $data_type !== '_none') {
        $intensity = 0.0;
        // Skip event if it doesn't have a result that uses the data type.
        foreach ($event->results as $result) {
          if ($result->entity->hasField(sprintf('data_%s', $data_type)) && !$result->entity->get(sprintf('data_%s', $data_type))->isEmpty()) {
            if (in_array($data_type, [
              'income',
              'expense',
            ])) {
              $intensity += $result->entity->get(sprintf('data_%s', $data_type))->entity->get(sprintf('field_transaction'))->value;
            }
            else {
              $intensity += $result->entity->get(sprintf('data_%s', $data_type))->entity->get(sprintf('field_%s', $data_type))->value;
            }
          }
        }
        // Skip if event has no matching results.
        if (empty($intensity) || $intensity === 0.0) {
          continue;
        }
        $largest_intensity = $intensity > $largest_intensity ? $intensity : $largest_intensity;
      }
      $places[] = [
        'gps' => [
          'latitude' => $event->location->latitude,
          'longitude' => $event->location->longitude,
        ],
        'intensity' => $intensity,
        'title' => $event->title->isEmpty() ? t('Event') : $event->title->value,
        'description' => sprintf('<p>%s<br>%s</p><p>%s</p>',
          $event->location->address,
          $event->location->extra_information,
          $event->description->value
        ),
        'url' => (new Url('entity.event.canonical', [
          'organization' => PathHelper::transliterate($event->parent->entity->organization->entity->label()),
          'group' => PathHelper::transliterate($event->parent->entity->label()),
          'event' => $event->id(),
        ]))->toString(),
      ];
    }
    // Make intensity relative to largest intensity.
    if (!empty($data_type) && $data_type !== '_none' && $largest_intensity > 0.0) {
      foreach ($places as &$place) {
        $place['intensity'] = $place['intensity'] / $largest_intensity;
      }
    }
    return $places;
  }

  /**
   * Returns a list of events formatted for leaflet.js.
   *
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization to get groups from.
   *
   * @return array
   *   Parameters for a map of groups.
   */
  public static function getGroupPlaces(Organization $organization) {
    $places = [];
    foreach (OrganizationHelper::getGroups($organization) as $group) {
      // Skip group if location is not set.
      if (empty($group->location->latitude)) {
        continue;
      }
      $places[] = [
        'gps' => [
          'latitude' => $group->location->latitude,
          'longitude' => $group->location->longitude,
        ],
        'title' => $group->label(),
        'description' => $group->description->value,
        'url' => (new Url('entity.group.canonical', [
          'organization' => PathHelper::transliterate($group->organization->entity->label()),
          'group' => PathHelper::transliterate($group->label()),
        ]))->toString(),
      ];
    }
    return $places;
  }

}
