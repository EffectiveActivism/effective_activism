<?php

namespace Drupal\effective_activism\ContentMigration\Export\CSV;

use Drupal;
use Drupal\Component\Utility\Random;
use Drupal\effective_activism\ContentMigration\ParserInterface;

/**
 * Processes batches of entity exports.
 */
class BatchProcess {

  /**
   * Parse content.
   *
   * @param \Drupal\effective_activism\ContentMigration\ParserInterface $parser
   *   The parser object to export items with.
   * @param array $context
   *   The context.
   */
  public static function process(ParserInterface $parser, array &$context) {
    // Set inital batch values.
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['results']['export_entity'] = $parser->getExportEntity();
    }
    $context['message'] = t('Exporting events...');
    foreach ($parser->getNextBatch($context['sandbox']['progress']) as $item) {
      $context['results']['items'][] = $parser->processItem($item);
      $context['sandbox']['progress']++;
    }
    // Inform batch about progess.
    $context['finished'] = $context['sandbox']['progress'] / $parser->getItemCount();
  }

  /**
   * Finishes a batch call.
   *
   * @param bool $success
   *   Wether or not any fatal PHP errors were encountered.
   * @param array $results
   *   The result of the export.
   * @param array $operations
   *   The operations performed.
   */
  public static function finished($success, array $results, array $operations) {
    if ($success) {
      $rows = $results['items'];
      $headers = CSVParser::buildHeaders($rows);
      $csv = CSVParser::convert($rows, $headers);
      // Save CSV string to file and attach it to export entity.
      $random_string = strtolower(preg_replace('/[^A-Za-z0-9 ]/', '', (new Random)->string(5)));
      $destination_uri = sprintf('%s://export/csv/%s/export-%d-%s.csv', file_default_scheme(), date('Y-m'), $results['export_entity']->id(), $random_string);
      $file = file_save_data($csv, $destination_uri);
      if ($file) {
        $file->save();
        $results['export_entity']
          ->set('field_file_csv', $file->id())
          ->save();
        drupal_set_message(Drupal::translation()->formatPlural(
          count($results['items']),
          'One event exported.',
          '@count events exported.'
        ));
      }
      else {
        drupal_set_message(t('An error occured during export.'), 'error');
      }
    }
    else {
      drupal_set_message(t('An error occured during export.'), 'error');
    }
  }

}
