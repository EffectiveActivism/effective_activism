<?php

namespace Drupal\effective_activism\Helper;

use Drupal;
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
   * @return ThirdPartyContent
   *   A new or existing ThirdPartyContent entity.
   */
  public static function getThirdPartyContent(array $parameters) {
    $third_party_content = NULL;
    // Preferentially use any existing entities that match this event.
    $query = Drupal::entityQuery('third_party_content');
    $query
      ->condition('type', $parameters['type'])
      ->condition('status', 1);
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
      $third_party_content->save();
    }
    return $third_party_content;
  }

}
