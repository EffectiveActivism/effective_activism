<?php

namespace Drupal\effective_activism\ContentMigration\Export;

use Drupal;

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
      // Create CSV file from results.
      
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
