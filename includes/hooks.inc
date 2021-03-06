<?php

/**
 * @file
 * Contains module hook implements.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Hook\CronHook;
use Drupal\effective_activism\Hook\EntityInsertHook;
use Drupal\effective_activism\Hook\EntityTypeAlterHook;
use Drupal\effective_activism\Hook\InlineEntityFormEntityFormAlterHook;
use Drupal\effective_activism\Hook\MailHook;
use Drupal\effective_activism\Hook\RequirementsHook;

/**
 * Implements hook_cron().
 */
function effective_activism_cron() {
  $hook = CronHook::getInstance();
  $hook->invoke([]);
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

/**
 * Implements hook_entity_type_alter().
 */
function effective_activism_entity_type_alter(array &$entity_types) {
  $hook = EntityTypeAlterHook::getInstance();
  $hook->invoke([
    'entity_types' => &$entity_types,
  ]);
}

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
 * Implements hook_mail().
 */
function effective_activism_mail($key, &$message, $params) {
  $hook = MailHook::getInstance();
  $hook->invoke([
    'key' => $key,
    'message' => &$message,
    'params' => $params,
  ]);
}

/**
 * Implements hook_requirements().
 */
function effective_activism_requirements($phase) {
  $hook = RequirementsHook::getInstance();
  return $hook->invoke([
    'phase' => $phase,
  ]);
}
