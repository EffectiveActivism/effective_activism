<?php

namespace Drupal\effective_activism\RouteProvider;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Import entities.
 *
 * @see Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class ImportHtmlRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);
    $entity_type_id = $entity_type->id();
    if ($collection_route = $this->getCollectionRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.collection", $collection_route);
    }
    if ($publish_form_route = $this->getPublishFormRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.publish_form", $publish_form_route);
    }
    return $collection;
  }

  /**
   * Gets the collection route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
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
        ->setRequirement('_custom_access', '\Drupal\effective_activism\AccessControlHandler\AccessControl::isAnyStaff');
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
      $operation = 'publish';
      $route
        ->setDefaults([
          '_form' => '\Drupal\effective_activism\Form\ImportPublishForm',
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

}
