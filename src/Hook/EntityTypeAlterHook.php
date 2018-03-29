<?php

namespace Drupal\effective_activism\Hook;

/**
 * Implements hook_entity_type_alter().
 */
class EntityTypeAlterHook implements HookInterface {

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
    $entity_types = &$args['entity_types'];
    $entity_types['taxonomy_term']->setListBuilderClass('Drupal\effective_activism\ListBuilder\TaxonomyTermListBuilder');
  }

}
