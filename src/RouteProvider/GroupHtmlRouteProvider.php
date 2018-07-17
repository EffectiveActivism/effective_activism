<?php

namespace Drupal\effective_activism\RouteProvider;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\effective_activism\Constant;
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
    if ($event_overview_route = $this->getEventsRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.events", $event_overview_route);
    }
    if ($export_overview_route = $this->getExportsRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.exports", $export_overview_route);
    }
    if ($import_overview_route = $this->getImportsRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.imports", $import_overview_route);
    }
    if ($publish_form_route = $this->getPublishFormRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.publish_form", $publish_form_route);
    }
    if ($result_overview_route = $this->getResultsRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.results", $result_overview_route);
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
          $entity_type_id => ['type' => Constant::ENTITY_GROUP],
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
          $entity_type_id => ['type' => Constant::ENTITY_GROUP],
        ]);
      return $route;
    }
  }

  /**
   * Gets the events route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getEventsRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('events')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('events'));
      $route
        ->setDefaults([
          '_entity_list' => 'event',
          '_title' => "Events",
        ])
        ->setRequirement('_entity_access', "{$entity_type_id}.view")
        ->setOption('parameters', [
          Constant::ENTITY_ORGANIZATION => ['type' => Constant::ENTITY_ORGANIZATION],
          $entity_type_id => ['type' => Constant::ENTITY_GROUP],
        ]);
      return $route;
    }
  }

  /**
   * Gets the exports overview route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getExportsRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('exports')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('exports'));
      $route
        ->setDefaults([
          '_entity_list' => 'export',
          '_title' => "Exports",
        ])
        ->setRequirement('_entity_access', "{$entity_type_id}.update")
        ->setOption('parameters', [
          Constant::ENTITY_ORGANIZATION => ['type' => Constant::ENTITY_ORGANIZATION],
          $entity_type_id => ['type' => Constant::ENTITY_GROUP],
        ]);
      return $route;
    }
  }

  /**
   * Gets the imports overview route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getImportsRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('imports')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('imports'));
      $route
        ->setDefaults([
          '_entity_list' => 'import',
          '_title' => "Imports",
        ])
        ->setRequirement('_entity_access', "{$entity_type_id}.update")
        ->setOption('parameters', [
          Constant::ENTITY_ORGANIZATION => ['type' => Constant::ENTITY_ORGANIZATION],
          $entity_type_id => ['type' => Constant::ENTITY_GROUP],
        ]);
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
          '_form' => '\Drupal\effective_activism\Form\GroupPublishForm',
          '_title' => "Publish {$entity_type->getLabel()}",
        ])
        ->setRequirement('_entity_create_access', $entity_type_id)
        ->setOption('parameters', [
          Constant::ENTITY_ORGANIZATION => ['type' => Constant::ENTITY_ORGANIZATION],
          $entity_type_id => ['type' => Constant::ENTITY_GROUP],
        ]);
      return $route;
    }
  }

  /**
   * Gets the results route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getResultsRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('results')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('results'));
      $route
        ->setDefaults([
          '_form' => '\Drupal\effective_activism\Form\ChartForm',
          '_title' => "Results",
        ])
        ->setRequirement('_entity_access', "{$entity_type_id}.update")
        ->setOption('parameters', [
          Constant::ENTITY_ORGANIZATION => ['type' => Constant::ENTITY_ORGANIZATION],
          $entity_type_id => ['type' => Constant::ENTITY_GROUP],
        ]);
      return $route;
    }
  }

}
