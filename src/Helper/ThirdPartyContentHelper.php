<?php

namespace Drupal\effective_activism\Helper;

use DateTimeZone;
use Drupal;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\TypedData\Exception\ReadOnlyException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Entity\Event;
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
    // Look for events with third-party content.
    $query = Drupal::entityQuery('event');
    $query
      ->exists('location.latitude')
      ->exists('location.longitude')
      ->condition('status', 1);
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

  /**
   * Adds, removes and reshuffles third-party content.
   *
   * @param \Drupal\effective_activism\Entity\Event $event
   *   The event containing the third-party content.
   */
  public static function shuffleThirdPartyContent(Event $event) {
    // If event address or times are changed, we need to update the
    // third-party content.
    try {
      $original_entity = Drupal::entityTypeManager()->getStorage('event')->loadUnchanged($event->id());
    }
    catch (InvalidPluginDefinitionException $exception) {
      Drupal::logger('effective_activism')->warning(sprintf('InvalidPluginDefinitionException when loading original entity %d', $event->id()));
      return;
    }
    catch (PluginNotFoundException $exception) {
      Drupal::logger('effective_activism')->warning(sprintf('PluginNotFoundException when loading original entity %d', $event->id()));
      return;
    }
    $start_date_value = $event->get('start_date')->getValue();
    $start_date = new DrupalDateTime($start_date_value[0]['value'], new DateTimezone(DATETIME_STORAGE_TIMEZONE));
    $original_start_date_value = $original_entity->get('start_date')->getValue();
    $original_start_date = new DrupalDateTime($original_start_date_value[0]['value'], new DateTimezone(DATETIME_STORAGE_TIMEZONE));
    $address = isset($event->location->address) ? $event->get('location')->address : NULL;
    $original_address = isset($event->original->location->address) ? $original_entity->get('location')->address : NULL;
    if (
      ($start_date->getTimestamp() !== $original_start_date->getTimestamp() ||
        $address !== $original_address) &&
      !empty($address) &&
      !$event->third_party_content->isEmpty()
    ) {
      $third_party_content_entities = [];
      foreach ($event->third_party_content->getValue() as $delta => $value) {
        $third_party_content = ThirdPartyContent::load($value['target_id']);
        // If other events are using the third-party content, remove it from
        // this event. The AddThirdPartyContent cron job will add to the event.
        $query = Drupal::entityQuery('event');
        $number_of_events = $query
          ->condition('third_party_content', $third_party_content->id())
          ->count()
          ->execute();
        // Only keep third-party content that isn't used by other events.
        if ((int) $number_of_events !== 1) {
          continue;
        }
        // If there is already a third-party content entity that matches
        // the new time and place values, do not use this one.
        $query = Drupal::entityQuery('third_party_content');
        $query
          ->condition('type', $third_party_content->bundle())
          ->condition('field_latitude', $event->get('location')->latitude)
          ->condition('field_longitude', $event->get('location')->longitude);
        if (in_array($third_party_content->bundle(), Constant::THIRD_PARTY_CONTENT_TIME_AWARE)) {
          $query->condition('field_timestamp', $event->get('start_date')->date->format('U'));
        }
        $number_of_similar_third_party_content = $query
          ->count()
          ->execute();
        // Only keep third-party content that is unique.
        if ((int) $number_of_similar_third_party_content > 0) {
          continue;
        }
        // We are now assured that the third-party content is not used
        // by other events and is unique. Update the third-party content
        // with new time and place values and set it to unpublished, so
        // it will be picked up by the populate cron job.
        $third_party_content->setPublished(FALSE);
        // Re-add location and time information, as applicable.
        $third_party_content->set('field_latitude', $event->get('location')->latitude);
        $third_party_content->set('field_longitude', $event->get('location')->longitude);
        if (in_array($third_party_content->bundle(), Constant::THIRD_PARTY_CONTENT_TIME_AWARE)) {
          $third_party_content->set('field_timestamp', $event->get('start_date')->date->format('U'));
        }
        $third_party_content->setNewRevision(TRUE);
        try {
          $third_party_content->save();
        }
        catch (EntityStorageException $exception) {
          Drupal::logger('effective_activism')->warning(sprintf('EntityStorageException when saving third-party content %d', $third_party_content->id()));
          continue;
        }
        $third_party_content_entities[] = ['target_id' => $third_party_content->id()];
      }
      // If the number of third_party_content references has changed, re-add.
      if (count($event->get('third_party_content')->getValue()) !== count($third_party_content_entities)) {
        try {
          $event->get('third_party_content')->setValue($third_party_content_entities);
        }
        catch (ReadOnlyException $exception) {
          Drupal::logger('effective_activism')->warning(sprintf('ReadOnlyException when setting value for event %d', $event->id()));
          return;
        }
      }
    }
    // If the change to the address it that it is removed, also remove any
    // references to third-party for the event.
    elseif (empty($address)) {
      $event->get('third_party_content')->setValue([]);
    }
  }

}
