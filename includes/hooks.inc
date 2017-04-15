<?php

/**
 * @file
 * Contains module hook implements.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Hook\EntityInsertHook;
use Drupal\effective_activism\Hook\FormAlterHook;
use Drupal\effective_activism\Hook\InlineEntityFormEntityFormAlterHook;
use Drupal\effective_activism\Hook\InlineEntityFormTableFieldsAlterHook;

/**
 * Implements hook_inline_entity_form_entity_form_alter().
 */
function effective_activism_inline_entity_form_entity_form_alter(&$entity_form, FormStateInterface &$form_state) {
  $hook = InlineEntityFormEntityFormAlterHook::getInstance();
  $hook->invoke([
    'entity_form' => &$entity_form,
    'form_state' => &$form_state,
  ]);
}

/**
 * Implements hook_entity_insert().
 */
function effective_activism_entity_insert(EntityInterface $entity) {
  $hook = EntityInsertHook::getInstance();
  $hook->invoke([
    'entity' => $entity,
  ]);
}
