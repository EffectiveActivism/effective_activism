<?php

namespace Drupal\effective_activism\Helper;

use Drupal;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Entity\Filter;
use Drupal\effective_activism\ContentMigration\Export\CSV\CSVParser;

/**
 * Helper functions for querying export.
 */
class ExportHelper {

  /**
   * Validation callback for the CSV export form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state to validate.
   */
  public static function validateCsv(array &$form, FormStateInterface $form_state) {
    $filter_id = isset($form_state->getValue('filter')[0]['target_id']) ? $form_state->getValue('filter')[0]['target_id'] : NULL;
    if (
      isset($filter_id) &&
      count(FilterHelper::getEvents(Filter::load($filter_id), 0, 0, FALSE)) === 0
    ) {
      $form_state->setErrorByName('', t('There are no events to export for this organization.'));
    }
  }

  /**
   * Retrieve column names from an exportable entity.
   *
   * @param string $entity_type_id
   *   The entity type id.
   *
   * @return array
   *   An array of column names.
   */
  public static function getColumns($entity_type_id) {
    $columns = [];
    $blacklist = array_merge([
      'field_latitude',
      'field_longitude',
    ], CSVParser::FIELDS_BLACKLIST);
    foreach (entity_get_bundles($entity_type_id) as $bundle => $info) {
      // Verify that user can access bundle.
      $bundle_entity = Drupal::entityTypeManager()
        ->getStorage($entity_type_id)
        ->load($bundle);
      if ($bundle_entity !== NULL && $bundle_entity->access('view') === FALSE) {
        continue;
      }
      $bundle_fields = Drupal::entityManager()->getFieldDefinitions($entity_type_id, $bundle);
      foreach ($bundle_fields as $field_name => $data) {
        if (!in_array($field_name, $blacklist)) {
          $columns[$field_name] = (string) $data->getLabel();
        }
      }
    }
    return array_unique($columns);
  }

}
