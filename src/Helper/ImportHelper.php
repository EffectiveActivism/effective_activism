<?php

namespace Drupal\effective_activism\Helper;

use Drupal;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Import;
use Drupal\effective_activism\ContentMigration\Import\CSV\CSVParser;
use Drupal\effective_activism\ContentMigration\Import\ICalendar\ICalendarParser;
use Drupal\effective_activism\ContentMigration\ParserValidationException;

/**
 * Helper functions for querying imports.
 */
class ImportHelper {

  /**
   * Get group events.
   *
   * @param \Drupal\effective_activism\Entity\Import $import
   *   The import to get events from.
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
  public static function getEvents(Import $import, $position = 0, $limit = 0, $load_entities = TRUE) {
    $query = Drupal::entityQuery('event')
      ->condition('import', $import->id())
      ->sort('start_date');
    if ($limit > 0) {
      $query->range($position, $limit + $position);
    }
    $result = $query->execute();
    return $load_entities ? Event::loadMultiple($result) : array_values($result);
  }

  /**
   * Validation callback for the CSV import form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state to validate.
   */
  public static function validateCsv(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    // Do not validate on file upload button trigger.
    if (!empty($trigger['#name']) && $trigger['#name'] !== 'field_file_csv_0_upload_button' && $trigger['#name'] !== 'field_file_csv_0_remove_button') {
      if (!empty($form_state->getValue('field_file_csv')[0]['fids'][0])) {
        $fid = $form_state->getValue('field_file_csv')[0]['fids'][0];
        $gid = $form_state->getValue('parent')[0]['target_id'];
        $parsed_csv = new CSVParser($fid, Group::load($gid), NULL);
        if (!$parsed_csv->validate()) {
          $form_state->setErrorByName('field_file_csv', $parsed_csv->getErrorMessage());
        }
      }
    }
  }

  /**
   * Validation callback for the iCalendar import form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state to validate.
   */
  public static function validateIcalendar(array &$form, FormStateInterface $form_state) {
    $field_url = $form_state->getValue('field_url');
    $parent = $form_state->getValue('parent');
    $gid = $parent[0]['target_id'];
    try {
      $parsedICalendar = new ICalendarParser($field_url[0]['uri'], Group::load($gid), NULL);
      if (!$parsedICalendar->validate()) {
        $form_state->setErrorByName('field_url', $parsedICalendar->getErrorMessage());
      }
    }
    catch (ParserValidationException $exception) {
      $form_state->setErrorByName('field_url', t('The iCalendar file is not found.'));
    }
  }

}
