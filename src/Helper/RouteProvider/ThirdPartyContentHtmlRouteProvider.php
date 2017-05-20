<?php

namespace Drupal\effective_activism\Helper\RouteProvider;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for ThirdPartyContent entities.
 *
 * @see Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class ThirdPartyContentHtmlRouteProvider extends DefaultHtmlRouteProvider {

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
    $add_page_route = $this->getAddPageRoute($entity_type);
    $collection->add("$entity_type_id.add_page", $add_page_route);
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
        ->setRequirement('_custom_access', '\Drupal\effective_activism\Permission\Permission::isAnyStaff');
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
      $bundle_entity_type_id = $entity_type->getBundleEntityType();
      $parameters = [
        $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        $bundle_entity_type_id => ['type' => 'entity:' . $bundle_entity_type_id],
      ];
      $route = new Route($entity_type->getLinkTemplate('add-form'));
      // Content entities with bundles are added via a dedicated controller.
      $route
        ->setDefaults([
          '_controller' => 'Drupal\effective_activism\Controller\ThirdPartyContentAddController::addForm',
          '_title_callback' => 'Drupal\effective_activism\Controller\ThirdPartyContentAddController::getAddFormTitle',
        ])
        ->setRequirement('_entity_create_access', $entity_type_id . ':{' . $bundle_entity_type_id . '}')
        ->setOption('parameters', $parameters)
        ->setOption('_admin_route', TRUE);
      return $route;
    }
  }

  /**
   * Gets the add page route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getAddPageRoute(EntityTypeInterface $entity_type) {
    $route = new Route("/effectiveactivism/{$entity_type->id()}/add");
    $route
      ->setDefaults([
        '_controller' => 'Drupal\effective_activism\Controller\ThirdPartyContentAddController::add',
        '_title' => "Add {$entity_type->getLabel()}",
      ])
      ->setRequirement('_entity_create_access', $entity_type->id());
    return $route;
  }

}
