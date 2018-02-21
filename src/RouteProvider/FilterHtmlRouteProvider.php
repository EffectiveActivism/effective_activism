<?php

namespace Drupal\effective_activism\RouteProvider;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\effective_activism\Constant;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Filter entities.
 *
 * @see Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class FilterHtmlRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);
    $entity_type_id = $entity_type->id();
    if ($publish_form_route = $this->getPublishFormRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.publish_form", $publish_form_route);
    }
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
   * Gets the canonical route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getCanonicalRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('canonical') && $entity_type->hasViewBuilderClass()) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('canonical'));
      $route
        ->addDefaults([
          '_entity_view' => "{$entity_type_id}.full",
          '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::title',
        ])
        ->setRequirement('_custom_access', '\Drupal\effective_activism\AccessControlHandler\AccessControl::fromRouteIsManager')
        ->setOption('parameters', [
          Constant::ENTITY_ORGANIZATION => ['type' => Constant::ENTITY_ORGANIZATION],
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);
      // Entity types with serial IDs can specify this in their route
      // requirements, improving the matching process.
      if ($this->getEntityTypeIdKeyType($entity_type) === 'integer') {
        $route->setRequirement($entity_type_id, '\d+');
      }
      return $route;
    }
  }

  /**
   * Gets the publish-form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getPublishFormRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('publish-form')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('publish-form'));
      $route
        ->setDefaults([
          '_form' => '\Drupal\effective_activism\Form\FilterPublishForm',
          '_title' => "Publish {$entity_type->getLabel()}",
        ])
        ->setRequirement('_entity_access', "{$entity_type_id}.update")
        ->setOption('parameters', [
          Constant::ENTITY_ORGANIZATION => ['type' => Constant::ENTITY_ORGANIZATION],
          $entity_type_id => ['type' => 'entity:' . Constant::ENTITY_FILTER],
        ]);
      return $route;
    }
  }

}
