<?php

namespace Drupal\effective_activism\Helper\RouteProvider;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Group entities.
 */
class GroupHtmlRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);
    $entity_type_id = $entity_type->id();
    if ($collection_route = $this->getCollectionRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.collection", $collection_route);
    }
    if ($add_form_route = $this->getAddFormRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.add_form", $add_form_route);
    }
    if ($publish_form_route = $this->getPublishFormRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.publish_form", $publish_form_route);
    }
    if ($publish_form_route = $this->getImportsRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.imports", $publish_form_route);
    }
    if ($publish_form_route = $this->getEventsRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.events", $publish_form_route);
    }
    return $collection;
  }

  /**
   * Gets the collection route.
   *
   * @param EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return Route|null
   *   The generated route, if available.
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('collection') && $entity_type->hasListBuilderClass()) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('collection'));
      $route
        ->setDefaults([
          '_entity_list' => $entity_type_id,
          '_title' => "{$entity_type->getLabel()} list",
        ])
        ->setRequirement('_custom_access', '\Drupal\effective_activism\Permission\Permission::isAnyStaff');
      return $route;
    }
  }

  /**
   * Gets the add-form route.
   *
   * @param EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return Route|null
   *   The generated route, if available.
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('add-form')) {
      $entity_type_id = $entity_type->id();
      $parameters = [
        $entity_type_id => ['type' => 'entity:' . $entity_type_id],
      ];
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
        ->setRequirement('_custom_access', '\Drupal\effective_activism\Permission\Permission::isAnyManager')
        ->setOption('parameters', $parameters);
      return $route;
    }
  }

  /**
   * Gets the publish-form route.
   *
   * @param EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return Route|null
   *   The generated route, if available.
   */
  protected function getPublishFormRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('publish-form')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('publish-form'));
      $route
        ->setDefaults([
          '_form' => '\Drupal\effective_activism\Form\Group\GroupPublishForm',
          '_title' => "Publish {$entity_type->getLabel()}",
        ])
        ->setRequirement('_entity_access', "{$entity_type_id}.update")
        ->setOption('parameters', [
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
   * Gets the events route.
   *
   * @param EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return Route|null
   *   The generated route, if available.
   */
  protected function getImportsRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('imports')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('imports'));
      $route
        ->setDefaults([
          '_controller' => '\Drupal\effective_activism\Controller\Overview\ImportOverviewController::routeCallback',
          '_title' => "Imports",
        ])
        ->setRequirement('_entity_access', "{$entity_type_id}.view")
        ->setOption('parameters', [
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
   * Gets the events route.
   *
   * @param EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return Route|null
   *   The generated route, if available.
   */
  protected function getEventsRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('events')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('events'));
      $route
        ->setDefaults([
          '_controller' => '\Drupal\effective_activism\Controller\Overview\EventOverviewController::routeCallback',
          '_title' => "Events",
        ])
        ->setRequirement('_entity_access', "{$entity_type_id}.view")
        ->setOption('parameters', [
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

}
