<?php

namespace Drupal\effective_activism\Helper;

use Drupal;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\effective_activism\Entity\ThirdPartyContent;

/**
 * Helper functions for third-party content.
 */
class ThirdPartyContentHelper {

  /**
   * Find a matching third-party content entity or create a new.
   *
   * @param array $parameters
   *   An associative array of type and field values for the entity.
   *
   * @return \Drupal\effective_activism\Entity\ThirdPartyContent
   *   A new or existing ThirdPartyContent entity.
   */
  public static function getThirdPartyContent(array $parameters) {
    $third_party_content = NULL;
    // Preferentially use any existing entities that match this event.
    $query = Drupal::entityQuery('third_party_content');
    $query
      ->condition('type', $parameters['type'])
      ->sort('created');
    foreach ($parameters as $field => $value) {
      if ($field !== 'type') {
        $query->condition($field, $value);
      }
    }
    $entity_ids = $query->execute();
    if (!empty($entity_ids)) {
      $third_party_content = ThirdPartyContent::load(array_pop($entity_ids));
    }
    else {
      $parameters['status'] = 0;
      $third_party_content = ThirdPartyContent::create($parameters);
      try {
        $third_party_content->save();
      }
      catch (EntityStorageException $exception) {
        return FALSE;
      }
    }
    return $third_party_content;
  }

  /**
   * 
   * Return events without a reference to a third-party content entity of type.
   *
   * @param string $type
   *   A third-party entity type.
   * @param int $batch_size
   *   Number of events to return.
   *
   * @return array
   *   A list of event ids of events that do not reference a third-party content
   *   entity of the specified type.
   */
  public static function getEventsWithoutThirdPartyContentType($type, $batch_size) {
    $event_ids = [];
    // Look for events with third-party content.
    $query = Drupal::entityQuery('event');
    $query
      ->exists('location.latitude')
      ->exists('location.longitude');
    $all_events_ids = $query->execute();
    // Look for events with third-party content.
    $query = Drupal::entityQuery('event');
    $query
      ->exists('location.latitude')
      ->exists('location.longitude')
      ->condition('third_party_content.entity.type', $type)
      ->condition('status', 1);
    $positive_events_ids = $query->execute();
    return array_slice(array_diff(array_values($all_events_ids), array_values($positive_events_ids)), 0, $batch_size);
  }

}
