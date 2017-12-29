<?php

namespace Drupal\effective_activism\Helper\Publish;

use Drupal;
use Drupal\Core\Entity\EntityInterface;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Entity\Import;
use Drupal\effective_activism\Entity\Export;

/**
 * Helper functions for publishing/unpublishing entities.
 */
class Publisher {

  const PUBLISH = TRUE;
  const UNPUBLISH = FALSE;
  const BATCHSIZE = 50;

  private $entity;
  private $entities = [];

  /**
   * Constructor.
   */
  public function __construct(EntityInterface $entity) {
    $this->entity = $entity;
    $this->entities = self::calculateEntities($entity);
  }

  /**
   * Returns the number of entities to process.
   *
   * @return int
   *   The number of entities to process.
   */
  public function getCount() {
    return count($this->entities);
  }

  /**
   * Recursively calculate the entities to process.
   *
   * @return array
   *   The entities to process.
   */
  private function calculateEntities(EntityInterface $entity) {
    $entities = [];
    switch (get_class($entity)) {
      case 'Drupal\effective_activism\Entity\Organization':
        $entities[] = [$entity->getEntityTypeId(), $entity->id()];
        $groups = Drupal::entityQuery('group')
          ->condition('organization', $entity->id())
          ->execute();
        if (!empty($groups)) {
          foreach (Group::loadMultiple($groups) as $group) {
            $entities = array_merge($entities, self::calculateEntities($group));
          }
        }
        break;

      case 'Drupal\effective_activism\Entity\Group':
        $entities[] = [$entity->getEntityTypeId(), $entity->id()];
        $events = Drupal::entityQuery('event')
          ->condition('parent', $entity->id())
          ->execute();
        if (!empty($events)) {
          foreach (Event::loadMultiple($events) as $event) {
            $entities = array_merge($entities, self::calculateEntities($event));
          }
        }
        $imports = Drupal::entityQuery('import')
          ->condition('parent', $entity->id())
          ->execute();
        if (!empty($imports)) {
          foreach (Import::loadMultiple($imports) as $import) {
            $entities[] = [$import->getEntityTypeId(), $import->id()];
          }
        }
        $exports = Drupal::entityQuery('export')
          ->condition('parent', $entity->id())
          ->execute();
        if (!empty($exports)) {
          foreach (Export::loadMultiple($exports) as $export) {
            $entities[] = [$export->getEntityTypeId(), $export->id()];
          }
        }
        break;

      case 'Drupal\effective_activism\Entity\Import':
        $entities[] = [$entity->getEntityTypeId(), $entity->id()];
        $events = Drupal::entityQuery('event')
          ->condition('import', $entity->id())
          ->execute();
        if (!empty($events)) {
          foreach (Event::loadMultiple($events) as $event) {
            $entities = array_merge($entities, self::calculateEntities($event));
          }
        }
        break;

      case 'Drupal\effective_activism\Entity\Export':
        $entities[] = [$entity->getEntityTypeId(), $entity->id()];
        break;

      case 'Drupal\effective_activism\Entity\Filter':
        $entities[] = [$entity->getEntityTypeId(), $entity->id()];
        break;

      case 'Drupal\effective_activism\Entity\Event':
        $entities[] = [$entity->getEntityTypeId(), $entity->id()];
        if (!empty($entity->get('results'))) {
          foreach ($entity->get('results') as $item) {
            $entities[] = [$item->entity->getEntityTypeId(), $item->entity->id()];
          }
        }
        break;

      case 'Drupal\effective_activism\Entity\Result':
        $entities[] = [$entity->getEntityTypeId(), $entity->id()];
        break;

      default:
        // No default publish action.
    }
    return $entities;
  }

  /**
   * Get the items to be published/unpublished.
   *
   * @param int $position
   *   The position to start from.
   *
   * @return array
   *   The items to process.
   */
  public function getNextBatch($position = 0) {
    return array_slice($this->entities, $position, self::BATCHSIZE);
  }

  /**
   * Publish an entity.
   *
   * @param array $item
   *   An array containing an entity type and id.
   *
   * @return int
   *   The entity id.
   */
  public static function publish(array $item) {
    $entity_storage = Drupal::entityTypeManager()->getStorage($item[0]);
    $entity = $entity_storage->load($item[1]);
    self::set($entity, self::PUBLISH);
    return $entity->id();
  }

  /**
   * Unpublish an entity.
   *
   * @param array $item
   *   An array containing an entity type and id.
   *
   * @return int
   *   The entity id.
   */
  public static function unpublish(array $item) {
    $entity_storage = Drupal::entityTypeManager()->getStorage($item[0]);
    $entity = $entity_storage->load($item[1]);
    self::set($entity, self::UNPUBLISH);
    return $entity->id();
  }

  /**
   * Set a publish state for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to set a state for.
   * @param bool $state
   *   The state to set.
   */
  private static function set(EntityInterface $entity, $state) {
    $entity->setPublished($state);
    $entity->setNewRevision();
    $entity->save();
  }

}
