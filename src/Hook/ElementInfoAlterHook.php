<?php

namespace Drupal\effective_activism\Hook;

use Drupal;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Helper\ImportParser\CSVParser;
use Drupal\effective_activism\Helper\ImportParser\ICalendarParser;

/**
 * Implements hook_element_info_alter().
 */
class ElementInfoAlterHook implements HookInterface {

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
    $types = &$args['types'];
    $types['datetime']['#process'][] = '\Drupal\effective_activism\Helper\EventHelper::setDateFormat';
  }
}
