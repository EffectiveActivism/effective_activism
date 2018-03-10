<?php

namespace Drupal\effective_activism\Hook;

use Drupal\effective_activism\ContentMigration\Export\CSV\CSVParser as ExportCSVParser;
use Drupal\effective_activism\ContentMigration\Import\CSV\CSVParser as ImportCSVParser;
use Drupal\effective_activism\ContentMigration\Import\ICalendar\ICalendarParser;

/**
 * Implements hook_entity_insert().
 */
class EntityInsertHook implements HookInterface {

  /**
   * An instance of this class.
   *
   * @var HookImplementation
   */
  private static $instance;

  /**
   * {@inheritdoc}
   */
  public static function getInstance() {
    if (!(self::$instance instanceof self)) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * {@inheritdoc}
   */
  public function invoke(array $args) {
    $entity = $args['entity'];
    switch ($entity->getEntityTypeId()) {
      case 'import':
        // If the import is a CSV file, import the content of it.
        if ($entity->bundle() === 'csv') {
          $field_file_csv = $entity->get('field_file_csv')->getValue();
          $group = $entity->get('parent')->entity;
          // Get CSV file.
          $csvParser = new ImportCSVParser($field_file_csv[0]['target_id'], $group, $entity);
          $batch = [
            'title' => t('Importing...'),
            'operations' => [
              [
                'Drupal\effective_activism\ContentMigration\Import\CSV\BatchProcess::process',
                [
                  $csvParser,
                ],
              ],
            ],
            'finished' => 'Drupal\effective_activism\ContentMigration\Import\CSV\BatchProcess::finished',
          ];
          batch_set($batch);
        }
        elseif ($entity->bundle() === 'icalendar') {
          $field_url = $entity->get('field_url')->getValue();
          $group = $entity->get('parent')->entity;
          // Get iCalendar.
          $icalendarParser = new ICalendarParser($field_url[0]['uri'], $group, $entity);
          $batch = [
            'title' => t('Importing...'),
            'operations' => [
              [
                'Drupal\effective_activism\ContentMigration\Import\ICalendar\BatchProcess::process',
                [
                  $icalendarParser,
                ],
              ],
            ],
            'finished' => 'Drupal\effective_activism\ContentMigration\Import\ICalendar\BatchProcess::finished',
          ];
          batch_set($batch);
        }
        break;

      case 'export':
        // If the export is a CSV file, export it to a file and add to export.
        if ($entity->bundle() === 'csv') {
          $field_file_csv = $entity->get('field_file_csv')->getValue();
          $organization = $entity->get('organization')->entity;
          // Get CSV file.
          $csvParser = new ExportCSVParser($organization, $entity);
          $batch = [
            'title' => t('Exporting...'),
            'operations' => [
              [
                'Drupal\effective_activism\ContentMigration\Export\CSV\BatchProcess::process',
                [
                  $csvParser,
                ],
              ],
            ],
            'finished' => 'Drupal\effective_activism\ContentMigration\Export\CSV\BatchProcess::finished',
          ];
          batch_set($batch);
        }
        break;
    }
  }

}
