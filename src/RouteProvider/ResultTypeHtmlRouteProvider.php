<?php

namespace Drupal\effective_activism\RouteProvider;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\effective_activism\Constant;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Result type entities.
 *
 * @see Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class ResultTypeHtmlRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);
    return $collection;
  }

  /**
   * Gets the add-form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('add-form')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('add-form'));
      // Use the add form handler, if available, otherwise default.
      $operation = 'default';
      if ($entity_type->getFormClass('add')) {
        $operation = 'add';
      }
      $route
        ->setDefaults([
          '_entity_form' => "{$entity_type_id}.{$operation}",
          '_title' => "Add {$entity_type->getLabel()}",
        ])
        ->setRequirement('_custom_access', '\Drupal\effective_activism\AccessControlHandler\AccessControl::fromRouteIsManager')
        ->setOption('parameters', [
          Constant::ENTITY_ORGANIZATION => ['type' => Constant::ENTITY_ORGANIZATION],
        ]);
      return $route;
    }
  }

  /**
   * Gets the edit-form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getEditFormRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('edit-form')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('edit-form'));
      // Use the edit form handler, if available, otherwise default.
      $operation = 'default';
      if ($entity_type->getFormClass('edit')) {
        $operation = 'edit';
      }
      $route
        ->setDefaults([
          '_entity_form' => "{$entity_type_id}.{$operation}",
          '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::editTitle',
        ])
        ->setRequirement('_entity_access', "{$entity_type_id}.update")
        ->setOption('parameters', [
          Constant::ENTITY_ORGANIZATION => ['type' => Constant::ENTITY_ORGANIZATION],
          $entity_type_id => ['type' => Constant::ENTITY_RESULT_TYPE],
        ]);
      return $route;
    }
  }

}
