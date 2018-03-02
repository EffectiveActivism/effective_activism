<?php

namespace Drupal\effective_activism\Helper;

use Drupal;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Entity\DataType;
use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Entity\ResultType;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Helper functions for querying groups.
 */
class ResultTypeHelper {

  /**
   * Load a result type by machine name and organization.
   *
   * @param string $value
   *   The value as typed by the user.
   * @param array $elements
   *   An array of form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   Whether the machine name exists within the organization or not.
   */
  public static function checkTypedImportNameExists($value, array $elements, FormStateInterface $form_state) {
    $organization_id = $form_state->getValue('organization');
    return !empty($organization_id) ? !self::isUniqueImportName($value, $organization_id) : NULL;
  }

  /**
   * Check if an import name is unique within an organization.
   *
   * @param string $importName
   *   The import name to check.
   * @param int $organization_id
   *   The organization id.
   *
   * @return bool
   *   Whether the import name exists within the organization or not.
   */
  public static function isUniqueImportName($importName, $organization_id) {
    $result = Drupal::entityQuery('result_type')
      ->condition('organization', $organization_id)
      ->condition('importname', $importName)
      ->count()
      ->execute();
    return $result === 0 ? TRUE : FALSE;
  }

  /**
   * Get events with results of the result type.
   *
   * @param string $import_name
   *   The import name of the result type.
   * @param int $organization_id
   *   The organization id.
   *
   * @return \Drupal\effective_activism\Entity\ResultType
   *   The loaded result type entity.
   */
  public static function getEvents(ResultType $result_type, $position = 0, $limit = 0, $load_entities = TRUE) {
    $results = Drupal::entityQuery('result')
      ->condition('type', $result_type->id())
      ->execute();
    if (empty($results)) {
      return [];
    }
    $query = Drupal::entityQuery('event')
      ->condition('results', $results, 'IN');
    if ($limit > 0) {
      $query->range($position, $limit + $position);
    }
    $result = $query->execute();
    return $load_entities ? Event::loadMultiple($result) : array_values($result);
  }

  /**
   * Load a result type by import name and organization id.
   *
   * @param string $import_name
   *   The import name of the result type.
   * @param int $organization_id
   *   The organization id.
   *
   * @return \Drupal\effective_activism\Entity\ResultType
   *   The loaded result type entity.
   */
  public static function getResultTypeByImportName($import_name, $organization_id) {
    $result = Drupal::entityQuery('result_type')
      ->condition('importname', $import_name)
      ->condition('organization', $organization_id)
      ->sort('organization')
      ->sort('label')
      ->execute();
    return !empty($result) ? ResultType::load(array_pop($result)) : NULL;
  }

  /**
   * Return a unique id based on an import name.
   *
   * @param string $import_name
   *   The import name to base the id on.
   *
   * @return string
   *   A unique entity id.
   */
  public static function getUniqueId($import_name) {
    $id = NULL;
    $result = NULL;
    while (TRUE) {
      // Id must be no more than 32 characters long.
      $id = uniqid(substr($import_name, 0, 19));
      $result = Drupal::entityQuery('result_type')
        ->condition('id', $id)
        ->count()
        ->execute();
      // If no existing result types have the id, return it.
      if ($result === 0) {
        break;
      }
    }
    return $id;
  }

  /**
   * Add a taxonomy field.
   *
   * @param \Drupal\effective_activism\Entity\ResultType $result_type
   *   The result type to add field to.
   */
  public static function addTaxonomyField(ResultType $result_type) {
    $entity_type_id = 'result';
    $bundle_id = $result_type->id();
    // Create unique field name.
    $oid = $result_type->organization;
    $field_name = sprintf('tags_%d', $oid);
    $vid = sprintf('tags_%d', $oid);
    // Check if field exists and create as necessary.
    $field_storage = FieldStorageConfig::loadByName($entity_type_id, $field_name);
    if (empty($field_storage)) {
      $field_storage = FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => $entity_type_id,
        'type' => 'entity_reference',
        'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
        'module' => 'core',
        'settings' => [
          'target_type' => 'taxonomy_term',
        ],
      ])->save();
    }
    // Check if instance exists and create as necessary.
    $field = FieldConfig::loadByName($entity_type_id, $bundle_id, $field_name);
    if (empty($field)) {
      // Create field.
      $field = FieldConfig::create([
        'field_name' => $field_name,
        'entity_type' => $entity_type_id,
        'bundle' => $bundle_id,
        'label' => t('Tags'),
        'weight' => 100,
      ]);
      $field->setRequired(FALSE)
        ->setSetting('target_type', 'taxonomy_term')
        ->setSetting('handler', 'default')
        ->setSetting('handler_settings', [
          'target_bundles' => [
            $vid => $vid,
          ],
          'auto_create' => 1,
        ])
        ->save();
    }
    // Set form display settings.
    entity_get_form_display($field->getTargetEntityTypeId(), $field->getTargetBundle(), 'default')
      ->setComponent($field->getName(), [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'STARTS_WITH',
        ],
      ])
      ->save();
    // Set view display settings.
    entity_get_display($field->getTargetEntityTypeId(), $field->getTargetBundle(), 'default')
      ->setComponent($field->getName(), [
        'type' => 'entity_reference_label',
      ])
      ->save();
  }

  /**
   * Keep result type fields updated.
   *
   * @param \Drupal\effective_activism\Entity\ResultType $result_type
   *   The result type to update.
   */
  public static function updateBundleSettings(ResultType $result_type) {
    $entity_type_id = 'result';
    $bundle_id = $result_type->id();
    $enabled_fields = [];
    if (isset($result_type->datatypes) && !empty($result_type->datatypes)) {
      foreach ($result_type->datatypes as $data_type => $enabled) {
        if ((bool) $enabled) {
          $field_name = 'data_' . $data_type;
          // Check if field exists and create as necessary.
          $field_storage = FieldStorageConfig::loadByName($entity_type_id, $field_name);
          if (empty($field_storage)) {
            $field_storage = FieldStorageConfig::create([
              'field_name' => $field_name,
              'entity_type' => $entity_type_id,
              'type' => 'entity_reference',
              'cardinality' => 1,
              'module' => 'core',
              'settings' => ['target_type' => 'data'],
            ])->save();
          }
          // Check if instance exists and create as necessary.
          $field = FieldConfig::loadByName($entity_type_id, $bundle_id, $field_name);
          if (empty($field)) {
            // Get label of DataType entity.
            $data_type_entity = DataType::load($data_type);
            $label = $data_type_entity->label();
            // Create field.
            $field = FieldConfig::create([
              'field_name' => $field_name,
              'entity_type' => $entity_type_id,
              'bundle' => $bundle_id,
              'label' => $label,
            ]);
            $field->setRequired(TRUE)
              ->setSetting('target_type', 'data')
              ->setSetting('handler', 'default')
              ->setSetting('handler_settings', [
                'target_bundles' => [
                  $data_type => $data_type,
                ],
              ])->save();
          }
          // Unhide any fields that already exists.
          self::enableFieldDisplay($field);
          // Add to enabled fields.
          $enabled_fields[] = $field_name;
        }
      }
    }
    // Hide any fields that arent enabled.
    foreach (Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type_id, $bundle_id) as $field_name => $field_definition) {
      if (strpos($field_name, 'data_') === 0 && !in_array($field_name, $enabled_fields)) {
        $field = FieldConfig::loadByName($entity_type_id, $bundle_id, $field_name);
        $field->setRequired(FALSE)->save();
        self::disableFieldDisplay($field);
      }
    }
  }

  /**
   * Set field display to simple inline entity form and require field.
   *
   * @param \Drupal\field\Entity\FieldConfig $field
   *   The field to enable.
   */
  private static function enableFieldDisplay(FieldConfig $field) {
    // Set form display settings.
    entity_get_form_display($field->getTargetEntityTypeId(), $field->getTargetBundle(), 'default')
      ->setComponent($field->getName(), [
        'type' => 'inline_entity_form_simple',
      ])
      ->save();
    // Set view display settings.
    entity_get_display($field->getTargetEntityTypeId(), $field->getTargetBundle(), 'default')
      ->setComponent($field->getName(), [
        'type' => 'entity_reference_entity_view',
      ])
      ->save();
  }

  /**
   * Set field display to hidden and do not require field.
   *
   * @param \Drupal\field\Entity\FieldConfig $field
   *   The field to disable.
   */
  private static function disableFieldDisplay(FieldConfig $field) {
    // Set form display settings.
    entity_get_form_display($field->getTargetEntityTypeId(), $field->getTargetBundle(), 'default')
      ->removeComponent($field->getName())
      ->save();
    // Set view display settings.
    entity_get_display($field->getTargetEntityTypeId(), $field->getTargetBundle(), 'default')
      ->removeComponent($field->getName())
      ->save();
  }

}
