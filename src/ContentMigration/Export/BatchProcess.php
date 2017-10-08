<?php

namespace Drupal\effective_activism\ContentMigration\Export;

use Drupal;
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
    }
    $context['message'] = t('Exporting items...');
    foreach ($parser->getNextBatch($context['sandbox']['progress']) as $item) {
      $item_id = $parser->processItem($item);
      $context['results'][] = $item_id;
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
      foreach ($results as $result) {
        // Escape comma and quotes.
        foreach ($result as $key => &$value) {
          $value = strpos($value, ',') !== FALSE ? sprintf('"%s"', str_replace('"', '""', $value)) : str_replace('"', '""', $value);
        }
        $row = implode(',', $result);
        $row = trim(preg_replace('/\s+/', ' ', $row));
        $csv .= sprintf('%s%s', $row, PHP_EOL);
      }
      //$file = file_save_data($csv, 'public://druplicon.png', FILE_EXISTS_REPLACE);
      dpm($csv);
      drupal_set_message(Drupal::translation()->formatPlural(
        count($results),
        'One item exported.',
        '@count items exported.'
      ));
    }
    else {
      drupal_set_message(t('An error occured during export.'), 'error');
    }
  }

}
