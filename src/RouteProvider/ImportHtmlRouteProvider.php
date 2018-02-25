<?php

namespace Drupal\effective_activism\RouteProvider;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\Controller\EntityController;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\effective_activism\Constant;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

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
      $route->setDefaults([
        '_entity_form' => "{$entity_type_id}.{$operation}",
        'entity_type_id' => $entity_type_id,
      ]);
      // If the entity has bundles, we can provide a bundle-specific title
      // and access requirements.
      $expected_parameter = $entity_type->getBundleEntityType() ?: $entity_type->getKey('bundle');
      // @todo: We have to check if a route contains a bundle in its path as
      //   test entities have inconsistent usage of "add-form" link templates.
      //   Fix it in https://www.drupal.org/node/2699959.
      if (($bundle_key = $entity_type->getKey('bundle')) && strpos($route->getPath(), '{' . $expected_parameter . '}') !== FALSE) {
        $route->setDefault('_title_callback', EntityController::class . '::addBundleTitle');
        // If the bundles are entities themselves, we can add parameter
        // information to the route options.
        if ($bundle_entity_type_id = $entity_type->getBundleEntityType()) {
          $bundle_entity_type = $this->entityTypeManager->getDefinition($bundle_entity_type_id);
          $route
            ->setDefault('bundle_parameter', $bundle_entity_type_id)
            ->setRequirement('_entity_create_access', $entity_type_id . ':{' . $bundle_entity_type_id . '}');
          $bundle_entity_parameter = [
            'type' => 'entity:' . $bundle_entity_type_id,
          ];
          if ($bundle_entity_type instanceof ConfigEntityTypeInterface) {
            // The add page might be displayed on an admin path. Even then, we
            // need to load configuration overrides so that, for example, the
            // bundle label gets translated correctly.
            // @see \Drupal\Core\ParamConverter\AdminPathConfigEntityConverter
            $bundle_entity_parameter['with_config_overrides'] = TRUE;
          }
          $route->setOption('parameters', [
            Constant::ENTITY_ORGANIZATION => ['type' => Constant::ENTITY_ORGANIZATION],
            Constant::ENTITY_GROUP => ['type' => Constant::ENTITY_GROUP],
            $bundle_entity_type_id => $bundle_entity_parameter,
          ]);
        }
        else {
          // If the bundles are not entities, the bundle key is used as the
          // route parameter name directly.
          $route
            ->setDefault('bundle_parameter', $bundle_key)
            ->setRequirement('_entity_create_access', $entity_type_id . ':{' . $bundle_key . '}');
        }
      }
      else {
        $route
          ->setDefault('_title_callback', EntityController::class . '::addTitle')
          ->setRequirement('_entity_create_access', $entity_type_id);
      }
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
          Constant::ENTITY_GROUP => ['type' => Constant::ENTITY_GROUP],
          $entity_type_id => ['type' => Constant::ENTITY_IMPORT],
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
          Constant::ENTITY_GROUP => ['type' => Constant::ENTITY_GROUP],
          $entity_type_id => ['type' => Constant::ENTITY_IMPORT],
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
          '_form' => '\Drupal\effective_activism\Form\ImportPublishForm',
          '_title' => "Publish {$entity_type->getLabel()}",
        ])
        ->setRequirement('_entity_create_access', $entity_type_id)
        ->setOption('parameters', [
          Constant::ENTITY_ORGANIZATION => ['type' => Constant::ENTITY_ORGANIZATION],
          Constant::ENTITY_GROUP => ['type' => Constant::ENTITY_GROUP],
          $entity_type_id => ['type' => Constant::ENTITY_IMPORT],
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
