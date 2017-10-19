<?php

namespace Drupal\effective_activism\ContentMigration;

/**
 * Provides an interface for defining import/export parsers.
 *
 * @ingroup effective_activism
 */
interface ParserInterface {

  /**
   * Get the number of items to be processed.
   *
   * @return int
   *   The number of items to import.
   */
  public function getItemCount();

  /**
   * Get the items to be processed.
   *
   * @param int $position
   *   The position to start from.
   *
   * @return array
   *   The items to process.
   */
  public function getNextBatch($position);

  /**
   * Process parsed items.
   *
   * @param mixed $item
   *   The values to parse.
   *
   * @return int|bool
   *   Returns item entity id or FALSE if import failed.
   */
  public function processItem($item);

  /**
   * Validates items.
   *
   * @return bool
   *   Whether the items are valid or not.
   */
  public function validate();

  /**
   * Returns a validation error message, if any.
   *
   * @return string|null
   *   The validation error message.
   */
  public function getErrorMessage();

}
