<?php

namespace Drupal\effective_activism\Hook;

/**
 * Implements hook_element_info_alter().
 */
class ElementInfoAlterHook implements HookInterface {

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
    $types = &$args['types'];
    $types['datetime']['#process'][] = '\Drupal\effective_activism\Helper\EventHelper::setDateFormat';
  }

}
