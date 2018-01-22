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
    if ($canonical_route = $this->getCanonicalRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.canonical", $canonical_route);
    }
    if ($add_form_route = $this->getAddFormRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.add_form", $add_form_route);
    }
    if ($publish_form_route = $this->getPublishFormRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.publish_form", $publish_form_route);
    }
    if ($import_overview_route = $this->getImportsRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.imports", $import_overview_route);
    }
    if ($event_overview_route = $this->getEventsRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.events", $event_overview_route);
    }
    if ($result_overview_route = $this->getResultsRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.results", $result_overview_route);
    }
    return $collection;
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
        ->setRequirement('_custom_access', '\Drupal\effective_activism\AccessControlHandler\AccessControl::fromRouteIsGroupStaff')
        ->setOption('parameters', [
          Constant::SLUG_ORGANIZATION => ['type' => Constant::ENTITY_ORGANIZATION],
          Constant::SLUG_GROUP => ['type' => Constant::ENTITY_GROUP],
        ]);
      return $route;
    }
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
          Constant::SLUG_ORGANIZATION => ['type' => Constant::ENTITY_ORGANIZATION],
          Constant::SLUG_GROUP => ['type' => Constant::ENTITY_GROUP],
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
          '_form' => '\Drupal\effective_activism\Form\Group\GroupPublishForm',
          '_title' => "Publish {$entity_type->getLabel()}",
        ])
        ->setRequirement('_custom_access', '\Drupal\effective_activism\AccessControlHandler\AccessControl::fromRouteIsGroupStaff')
        ->setOption('parameters', [
          Constant::SLUG_ORGANIZATION => ['type' => Constant::ENTITY_ORGANIZATION],
          Constant::SLUG_GROUP => ['type' => Constant::ENTITY_GROUP],
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
        ->setRequirement('_custom_access', '\Drupal\effective_activism\AccessControlHandler\AccessControl::fromRouteIsGroupStaff')
        ->setOption('parameters', [
          Constant::SLUG_ORGANIZATION => ['type' => Constant::ENTITY_ORGANIZATION],
          Constant::SLUG_GROUP => ['type' => Constant::ENTITY_GROUP],
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
        ->setRequirement('_custom_access', '\Drupal\effective_activism\AccessControlHandler\AccessControl::fromRouteIsGroupStaff')
        ->setOption('parameters', [
          Constant::SLUG_ORGANIZATION => ['type' => Constant::ENTITY_ORGANIZATION],
          Constant::SLUG_GROUP => ['type' => Constant::ENTITY_GROUP],
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
          '_entity_list' => 'result',
          '_title' => "Results",
        ])
        ->setRequirement('_custom_access', '\Drupal\effective_activism\AccessControlHandler\AccessControl::fromRouteIsGroupStaff')
        ->setOption('parameters', [
          Constant::SLUG_ORGANIZATION => ['type' => Constant::ENTITY_ORGANIZATION],
          Constant::SLUG_GROUP => ['type' => Constant::ENTITY_GROUP],
        ]);
      return $route;
    }
  }

}
