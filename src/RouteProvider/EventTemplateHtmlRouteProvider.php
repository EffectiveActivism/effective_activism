<?php

namespace Drupal\effective_activism\RouteProvider;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\effective_activism\Constant;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Event template entities.
 *
 * @see Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class EventTemplateHtmlRouteProvider extends DefaultHtmlRouteProvider {

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
        ->setRequirement('_entity_create_access', $entity_type_id)
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
        ->setRequirement('_entity_access', "{$entity_type_id}.view")
        ->setOption('parameters', [
          Constant::ENTITY_ORGANIZATION => ['type' => Constant::ENTITY_ORGANIZATION],
          $entity_type_id => ['type' => Constant::ENTITY_EVENT_TEMPLATE],
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
   * Gets the delete-form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getDeleteFormRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('delete-form')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('delete-form'));
      $route
        ->addDefaults([
          '_entity_form' => "{$entity_type_id}.delete",
          '_title_callback' => '\\Drupal\\Core\\Entity\\Controller\\EntityController::deleteTitle',
        ])
        ->setRequirement('_entity_access', "{$entity_type_id}.delete")
        ->setOption('parameters', [
          Constant::ENTITY_ORGANIZATION => ['type' => Constant::ENTITY_ORGANIZATION],
          $entity_type_id => ['type' => Constant::ENTITY_EVENT_TEMPLATE],
        ]);
      // Entity types with serial IDs can specify this in their route
      // requirements, improving the matching process.
      if ($this->getEntityTypeIdKeyType($entity_type) === 'integer') {
        $route->setRequirement($entity_type_id, '\\d+');
      }
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
          $entity_type_id => ['type' => Constant::ENTITY_EVENT_TEMPLATE],
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
          '_form' => '\Drupal\effective_activism\Form\EventTemplatePublishForm',
          '_title' => "Publish {$entity_type->getLabel()}",
        ])
        ->setRequirement('_entity_create_access', $entity_type_id)
        ->setOption('parameters', [
          Constant::ENTITY_ORGANIZATION => ['type' => Constant::ENTITY_ORGANIZATION],
          $entity_type_id => ['type' => Constant::ENTITY_EVENT_TEMPLATE],
        ]);
      // Entity types with serial IDs can specify this in their route
      // requirements, improving the matching process.
      if ($this->getEntityTypeIdKeyType($entity_type) === 'integer') {
        $route->setRequirement($entity_type_id, '\d+');
      }
      return $route;
    }
  }

}
