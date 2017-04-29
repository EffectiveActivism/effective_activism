<?php

namespace Drupal\effective_activism\Helper\Publish;

use Drupal;

/**
 * Processes batches of entity publish/unpublish actions.
 */
class BatchProcess {

  /**
   * Imports from a parser.
   *
   * @param Publisher $publisher
   *   The publisher object to unpublish entities with.
   * @param array $context
   *   The context.
   */
  public static function unpublish(Publisher $publisher, array &$context) {
    // Set inital batch values.
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
    }
    $context['message'] = t('Unpublishing items...');
    foreach ($publisher->getNextBatch($context['sandbox']['progress']) as $item) {
      $context['results'][] = $publisher->unpublish($item);
      $context['sandbox']['progress']++;
    }
    // Inform batch about progess.
    $context['finished'] = $context['sandbox']['progress'] / $publisher->getCount();
  }

  /**
   * Publishes one or more entities.
   *
   * @param Publisher $publisher
   *   The publisher object to publish entities with.
   * @param array $context
   *   The context.
   */
  public static function publish(Publisher $publisher, array &$context) {
    // Set inital batch values.
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
    }
    $context['message'] = t('Publishing items...');
    foreach ($publisher->getNextBatch($context['sandbox']['progress']) as $item) {
      $context['results'][] = $publisher->publish($item);
      $context['sandbox']['progress']++;
    }
    // Inform batch about progess.
    $context['finished'] = $context['sandbox']['progress'] / $publisher->getCount();
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
  public static function unpublished($success, array $results, array $operations) {
    if ($success) {
      drupal_set_message(Drupal::translation()->formatPlural(
        count($results),
        'One item unpublished.',
        '@count items unpublished.'
      ));
    }
    else {
      drupal_set_message(t('An error occured during unpublishing.'), 'error');
    }
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
  public static function published($success, array $results, array $operations) {
    if ($success) {
      drupal_set_message(Drupal::translation()->formatPlural(
        count($results),
        'One item published.',
        '@count items published.'
      ));
    }
    else {
      drupal_set_message(t('An error occured during publishing.'), 'error');
    }
  }

}
