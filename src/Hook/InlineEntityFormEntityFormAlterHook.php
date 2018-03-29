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
    $entity_form = &$args['entity_form'];
    $form_state = &$args['form_state'];
    $entity_type = $entity_form['#entity_type'];
    if ($entity_type === 'result') {
      $start_date = $form_state->getValue('start_date');
      $end_date = $form_state->getValue('end_date');
      if (
        !empty($start_date[0]['value']['object']) &&
        !empty($end_date[0]['value']['object'])
      ) {
        $interval = $start_date[0]['value']['object']->diff($end_date[0]['value']['object']);
        // Cap interval calculation at one month as longer spans are
        // unsupported at the moment.
        if (
          $interval->format('%m') === '0' &&
          $interval->format('%y') === '0'
        ) {
          $entity_form['duration_days']['widget']['#default_value'] = $interval->format('%d');
          $entity_form['duration_hours']['widget']['#default_value'] = $interval->format('%h');
          $entity_form['duration_minutes']['widget']['#default_value'] = $interval->format('%i');
        }
      }
    }
    if (in_array($entity_type, self::INLINE_ENTITY_TYPES)) {
      $entity_form['#theme'] = self::INLINE_ENTITY_FORM_TEMPLATES[$entity_type];
      // Hide revision and user fields.
      $entity_form['user_id']['#attributes']['class'][] = 'hidden';
      $entity_form['revision_log_message']['#attributes']['class'][] = 'hidden';
    }
  }

}
