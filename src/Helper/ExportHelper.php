<?php

namespace Drupal\effective_activism\Helper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Entity\Filter;
use Drupal\effective_activism\Helper\FilterHelper;

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

}
