<?php

namespace Drupal\effective_activism\Hook;

use Drupal\effective_activism\Helper\ImportParser\CSVParser;

/**
 * Implements hook_entity_insert().
 */
class EntityInsertHook implements HookInterface {

  /**
   * An instance of this class.
   *
   * @var HookImplementation $instance
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
          $csvParser = new CSVParser($field_file_csv[0]['target_id'], $group, $entity);
          $batch = [
            'title' => t('Importing...'),
            'operations' => [
              [
                'Drupal\effective_activism\Helper\ImportParser\BatchProcess::import',
                [
                  $csvParser,
                ],
              ],
            ],
            'finished' => 'Drupal\effective_activism\Helper\ImportParser\BatchProcess::finished',
          ];
          batch_set($batch);
        }
        break;
    }
  }

}
