<?php

namespace Drupal\effective_activism\ContentMigration\Export\CSV;

use Drupal;
use Drupal\Component\Utility\Random;
use Drupal\effective_activism\ContentMigration\ParserInterface;
use Drupal\file\Entity\File;

/**
 * Processes batches of entity exports.
 */
class BatchProcess {

  /**
   * Parse content.
   *
   * @param Drupal\effective_activism\ContentMigration\ParserInterface $parser
   *   The parser object to import items with.
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
   *   The result of the import.
   * @param array $operations
   *   The operations performed.
   */
  public static function finished($success, array $results, array $operations) {
    if ($success) {
      // Convert result to CSV and save as file to export entity.
      $rows = [];
      foreach ($results['items'] as $result) {
        $row = [];
        // Escape comma and quotes.
        foreach ($result as $key => $value) {
          if (is_array($value)) {
            foreach ($value as $entityArray) {
              $row = array_merge($row, self::collapseEntityArray($key, $entityArray));
            }
          }
          else {
            $row[$key] = self::formatValue($value);
          }
        }
        $rows[] = $row;
      }
      // Calculate headers and force some column names to be first.
      $headers = [
        'title',
        'description',
      ];
      foreach ($rows as $row) {
        $keys = array_keys($row);
        $headers = array_unique(array_merge($headers, $keys));
      }
      $csv = sprintf('%s%s', implode(',', $headers), PHP_EOL);
      // Build row order from headers.
      foreach ($rows as $row) {
        // Sort row by headers.
        $csv_row = [];
        foreach ($headers as $header) {
          if (isset($row[$header])) {
            $csv_row[] = self::formatValue($row[$header]);
          }
          else {
            $csv_row[] = NULL;
          }
        }
        $csv .= sprintf('%s%s', trim(preg_replace('/\s+/', ' ', implode(',', $csv_row))), PHP_EOL);
      }
      // Save CSV string to file and attach it to export entity.
      $random_string = strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', (new Random)->string(5)));
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

  /**
   * 
   */
  private static function collapseEntityArray($entity_bundle_id, $array) {
    $row = [];
    foreach ($array as $field_name => $value) {
      if (is_array($value)) {
        $row = array_merge($row, self::collapseEntityArray(sprintf('%s_%s', $entity_bundle_id, $field_name), $value));
      }
      else {
        $row[sprintf('%s_%s', $entity_bundle_id, $field_name)] = self::formatValue($value);
      }
    }
    return $row;
  }

  /**
   * 
   */
  private static function formatValue($value) {
    return strpos($value, ',') !== FALSE ? sprintf('"%s"', str_replace('"', '""', $value)) : str_replace('"', '""', $value);
  }

}
