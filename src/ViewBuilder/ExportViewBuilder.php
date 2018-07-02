<?php

namespace Drupal\effective_activism\ViewBuilder;

use Drupal;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Provides functionality for building the Export entity view.
 */
class ExportViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = parent::view($entity, $view_mode, $langcode);
    $export = $build['#export'];
    // Export view pages can be viewed from two locations, organization level and group level.
    // Only one location is valid, so we check to make sure that an invalid choice hasn't been made.
    if (
      (Drupal::request()->get('group') !== NULL && $export->parent->isEmpty()) ||
      (Drupal::request()->get('group') === NULL && !$export->parent->isEmpty())
    ) {
      drupal_set_message($this->t('Please view this page from the proper path.'), 'error');
      return [];
    }
    return $build;
  }
}