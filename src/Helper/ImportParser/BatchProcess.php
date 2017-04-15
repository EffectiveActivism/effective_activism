<?php

namespace Drupal\effective_activism\Helper\ImportParser;

use Drupal\effective_activism\Entity\Import;

/**
 * Processes batches of item imports.
 */
class BatchProcess {

  /**
   * Imports from a parser.
   *
   * @param Drupal\effective_activism\Helper\ImportParser\ParserInterface $parser
   *   The parser object to import items with.
   * @param array $context
   *   The context.
   */
  public static function import(ParserInterface $parser, array &$context) {
    // Set inital batch values.
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 1;
    }
    $context['message'] = t('Importing items...');
    foreach ($parser->getNextBatch($context['sandbox']['progress']) as $item) {
      $item_id = $parser->importItem($item);
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
      drupal_set_message(\Drupal::translation()->formatPlural(
        count(array_unique($results, SORT_NUMERIC)),
        'One item imported.',
        '@count items imported.'
      ));
    }
    else {
      drupal_set_message(t('An error occured during import.'), 'error');
    }
  }

}
