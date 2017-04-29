<?php

namespace Drupal\effective_activism\Hook;

/**
 * Implements hook_inline_entity_form_entity_form_alter().
 */
class InlineEntityFormEntityFormAlterHook implements HookInterface {

  /**
   * Inline entities.
   */
  const INLINE_ENTITY_TYPES = [
    'data',
    'person',
    'result',
  ];

  /**
   * Inline entity forms.
   */
  const INLINE_ENTITY_FORM_TEMPLATES = [
    'data' => 'inline_entity_form_data',
    'person' => 'inline_entity_form_person',
    'result' => 'inline_entity_form_result',
  ];

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
    $entity_form = &$args['entity_form'];
    $form_state = &$args['form_state'];
    $entity_type = $entity_form['#entity_type'];
    if (in_array($entity_type, self::INLINE_ENTITY_TYPES)) {
      $entity_form['#theme'] = self::INLINE_ENTITY_FORM_TEMPLATES[$entity_type];
      // Hide revision and user fields.
      $entity_form['user_id']['#attributes']['class'][] = 'hidden';
      $entity_form['revision_log_message']['#attributes']['class'][] = 'hidden';
    }
  }

}
