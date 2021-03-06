<?php

namespace Drupal\effective_activism\ContentMigration\Import;

use Drupal\Core\Entity\EntityInterface;
use Drupal\effective_activism\Entity\Data;
use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Result;
use Drupal\effective_activism\Helper\ResultTypeHelper;
use Drupal\taxonomy\Entity\Term;

/**
 * Entity parsing functions.
 */
abstract class EntityImportParser {

  const INVALID_HEADERS = -1;

  const INVALID_DATE = -2;

  const INVALID_LOCATION = -3;

  const INVALID_RRULE = -5;

  const INVALID_RESULT = -6;

  const INVALID_DATA = -7;

  const INVALID_EVENT = -8;

  const WRONG_ROW_COUNT = -9;

  const PERMISSION_DENIED = -10;

  /**
   * Filters standard entity fields.
   *
   * @param string $type
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   *
   * @return array
   *   A filtered array of fields.
   */
  private function getFields($type, $bundle = NULL) {
    if (empty($bundle)) {
      $bundle = $type;
    }
    $fields = array_keys(\Drupal::entityManager()->getFieldDefinitions($type, $bundle));
    // Do not include standard fields.
    unset($fields[array_search('id', $fields)]);
    unset($fields[array_search('uuid', $fields)]);
    unset($fields[array_search('user_id', $fields)]);
    unset($fields[array_search('status', $fields)]);
    unset($fields[array_search('langcode', $fields)]);
    unset($fields[array_search('default_langcode', $fields)]);
    unset($fields[array_search('created', $fields)]);
    unset($fields[array_search('changed', $fields)]);
    // Also exclude revision fields.
    unset($fields[array_search('revision_id', $fields)]);
    unset($fields[array_search('revision_created', $fields)]);
    unset($fields[array_search('revision_user', $fields)]);
    unset($fields[array_search('revision_log_message', $fields)]);
    unset($fields[array_search('revision_default', $fields)]);
    $fields = array_values($fields);
    return $fields;
  }

  /**
   * Validates an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to validate.
   * @param array $fieldsToIgnore
   *   Validation errors to ignore.
   *
   * @return bool
   *   TRUE if entity has no violations, FALSE otherwise.
   */
  private function validateEntity(EntityInterface $entity, array $fieldsToIgnore = []) {
    $isValid = TRUE;
    if ($entity) {
      foreach ($entity->validate() as $violation) {
        if (!in_array($violation->getPropertyPath(), $fieldsToIgnore)) {
          $isValid = FALSE;
        }
      }
    }
    return $isValid;
  }

  /**
   * Validates a result.
   *
   * @param array $values
   *   Data to validate as result entity.
   * @param string $import_name
   *   The import name.
   * @param \Drupal\effective_activism\Entity\Group $group
   *   The group.
   *
   * @return bool
   *   TRUE if result is valid, FALSE otherwise.
   */
  public function validateResult(array $values, $import_name, Group $group) {
    // Get organization from group.
    $organization_id = empty($group->get('organization')->entity) ? $group->id() : $group->get('organization')->entity->id();
    $result_type = ResultTypeHelper::getResultTypeByImportName($import_name, $organization_id);
    // Make sure the result type is valid.
    if (empty($result_type)) {
      return FALSE;
    }
    // Make sure the result type is allowed for the group.
    if (!in_array($group->id(), $result_type->groups)) {
      return FALSE;
    }
    $fields = $this->getFields('result', $result_type->id());
    $fields_to_ignore = [];
    foreach ($fields as $key => $field) {
      // Validate any data entities identified by field name 'data_*'.
      if (strpos($field, 'data_') === 0) {
        $data_type = substr($field, strlen('data_'));
        if (!$this->validateData([
          $data_type,
          $values[$key],
        ], $data_type)) {
          return FALSE;
        }
        // Do not validate this field for the result entity.
        $values[$key] = NULL;
        $fields_to_ignore[] = $field;
      }
      // Validate any tags.
      elseif (strpos($field, 'tags_') === 0) {
        $vid = $field;
        if (isset($values[$key]) && !$this->validateTerm([
          $vid,
          $values[$key],
        ])) {
          return FALSE;
        }
        // Do not validate this field for the result entity.
        $values[$key] = NULL;
        $fields_to_ignore[] = $field;
      }
      // Replace import name with result type id.
      elseif ($field === 'type') {
        $values[$key] = $result_type->id();
      }
    }
    $data = array_combine($fields, $values);
    return $this->validateEntity(Result::create($data), $fields_to_ignore);
  }

  /**
   * Validates a data entity.
   *
   * @param array $values
   *   Data to validate as data entity.
   * @param string $bundle
   *   The bundle of the data entity.
   *
   * @return bool
   *   TRUE if data is valid, FALSE otherwise.
   */
  public function validateData(array $values, $bundle) {
    $fields = $this->getFields('data', $bundle);
    $data = array_combine($fields, $values);
    return $this->validateEntity(Data::create($data));
  }

  /**
   * Validates a term entity.
   *
   * @param array $values
   *   Data to validate as term entity.
   *
   * @return bool
   *   TRUE if data is valid, FALSE otherwise.
   */
  public function validateTerm(array $values) {
    $fields = ['vid', 'name'];
    $data = array_combine($fields, $values);
    return $this->validateEntity(Term::create($data));
  }

  /**
   * Validates an event entity.
   *
   * @param array $values
   *   Values to validate as an event entity.
   *
   * @return bool
   *   TRUE if event is valid, FALSE otherwise.
   */
  public function validateEvent(array $values) {
    $fields = $this->getFields('event');
    $data = array_combine($fields, $values);
    return $this->validateEntity(Event::create($data));
  }

  /**
   * Imports a result entity.
   *
   * @param array $values
   *   Values to import as a result entity.
   * @param string $importName
   *   The import name of the result entity.
   * @param \Drupal\effective_activism\Entity\Group $group
   *   The group to import to.
   *
   * @return \Drupal\effective_activism\Entity\Result|bool
   *   The result entity or FALSE if import failed.
   */
  public function importResult(array $values, $importName, Group $group) {
    // Get organization from group.
    $organizationId = $group->get('organization')->entity->id();
    $resultType = ResultTypeHelper::getResultTypeByImportName($importName, $organizationId);
    $fields = $this->getFields('result', $resultType->id());
    foreach ($fields as $key => $field) {
      // Create any data entities identified by field name 'data_*'.
      if (strpos($field, 'data_') === 0) {
        $dataType = substr($field, strlen('data_'));
        $dataEntity = $this->importData($values[$key], $dataType);
        // Overwrite value with corresponding data entity.
        $values[$key] = $dataEntity->id();
      }
      // Create or add any term entities.
      elseif (strpos($field, 'tags_') === 0) {
        $vid = $field;
        $termEntity = $this->importTerm([$vid, $values[$key]]);
        // Overwrite value with corresponding data entity.
        $values[$key] = $termEntity->id();
      }
      // Replace import name with result type id.
      elseif ($field === 'type') {
        $values[$key] = $resultType->id();
      }
    }
    $data = array_combine($fields, $values);
    $entity = Result::create($data);
    if ($entity->save()) {
      return $entity;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Imports a data entity.
   *
   * @param string $dataValue
   *   The data value.
   * @param string $bundle
   *   The bundle of the result entity.
   *
   * @return \Drupal\effective_activism\Entity\Data|bool
   *   The data entity or FALSE if import failed.
   */
  public function importData($dataValue, $bundle) {
    $fields = $this->getFields('data', $bundle);
    $data = array_combine($fields, [
      $bundle,
      $dataValue,
    ]);
    $entity = Data::create($data);
    if ($entity->save()) {
      return $entity;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Imports a term entity if none exists, or adds existing.
   *
   * @param array $values
   *   Values to import as a term.
   *
   * @return \Drupal\taxonomy\Entity\Term|bool
   *   The term entity or FALSE if import failed.
   */
  public function importTerm(array $values) {
    $fields = ['vid', 'name'];
    $data = array_combine($fields, $values);
    $existing_terms = taxonomy_term_load_multiple_by_name($data['name'], $data['vid']);
    // If term doesn't exist, create it.
    if (empty($existing_terms)) {
      $entity = Term::create($data);
      if ($entity->save()) {
        return $entity;
      }
      else {
        return FALSE;
      }
    }
    else {
      return array_pop($existing_terms);
    }
  }

  /**
   * Imports an event entity.
   *
   * @param array $values
   *   Values to import as an event.
   *
   * @return \Drupal\effective_activism\Entity\Event|bool
   *   The event entity or FALSE if import failed.
   */
  public function importEvent(array $values) {
    $fields = $this->getFields('event');
    $data = array_combine($fields, $values);
    $entity = Event::create($data);
    if ($entity->save()) {
      return $entity;
    }
    else {
      return FALSE;
    }
  }

}
