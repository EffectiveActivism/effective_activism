<?php

namespace Drupal\effective_activism\Helper;

use Drupal\Core\Entity\EntityInterface;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Entity\Import;

/**
 * Helper functions for publishing/unpublishing entities.
 */
class PublishHelper {

  const PUBLISH = TRUE;
  const UNPUBLISH = FALSE;

  /**
   * Publish an entity.
   *
   * @param EntityInterface $entity
   *    The entity to publish.
   */
  public static function publish(EntityInterface $entity) {
    switch (get_class($entity)) {
      case 'Drupal\effective_activism\Entity\Organization':
        self::set($entity, self::PUBLISH);
        $groups = \Drupal::entityQuery('group')
          ->condition('organization', $entity->id())
          ->execute();
        if (!empty($groups)) {
          foreach (Group::loadMultiple($groups) as $group) {
            self::publish($group);
          }
        }
        break;

      case 'Drupal\effective_activism\Entity\Group':
        self::set($entity, self::PUBLISH);
        $events = \Drupal::entityQuery('event')
          ->condition('parent', $entity->id())
          ->execute();
        if (!empty($events)) {
          foreach (Event::loadMultiple($events) as $event) {
            self::publish($event);
          }
        }
        $imports = \Drupal::entityQuery('import')
          ->condition('parent', $entity->id())
          ->execute();
        if (!empty($imports)) {
          foreach (Import::loadMultiple($imports) as $import) {
            self::set($import, self::PUBLISH);
          }
        }
        break;

      case 'Drupal\effective_activism\Entity\Import':
        self::set($entity, self::PUBLISH);
        $events = \Drupal::entityQuery('event')
          ->condition('import', $entity->id())
          ->execute();
        if (!empty($events)) {
          foreach (Event::loadMultiple($events) as $event) {
            self::publish($event);
          }
        }
        break;

      case 'Drupal\effective_activism\Entity\Event':
        self::set($entity, self::PUBLISH);
        if (!empty($entity->get('results'))) {
          foreach ($entity->get('results') as $item) {
            self::publish($item->entity);
          }
        }
        break;

      case 'Drupal\effective_activism\Entity\Result':
        self::set($entity, self::PUBLISH);
        break;

      default:
        // No default publish action.
    }
  }

  /**
   * Unpublish an entity.
   *
   * @param EntityInterface $entity
   *    The entity to unpublish.
   */
  public static function unpublish(EntityInterface $entity) {
    switch (get_class($entity)) {
      case 'Drupal\effective_activism\Entity\Organization':
        self::set($entity, self::UNPUBLISH);
        $groups = \Drupal::entityQuery('group')
          ->condition('organization', $entity->id())
          ->execute();
        if (!empty($groups)) {
          foreach (Group::loadMultiple($groups) as $group) {
            self::unpublish($group);
          }
        }
        break;

      case 'Drupal\effective_activism\Entity\Group':
        self::set($entity, self::UNPUBLISH);
        $events = \Drupal::entityQuery('event')
          ->condition('parent', $entity->id())
          ->execute();
        if (!empty($events)) {
          foreach (Event::loadMultiple($events) as $event) {
            self::unpublish($event);
          }
        }
        $imports = \Drupal::entityQuery('import')
          ->condition('parent', $entity->id())
          ->execute();
        if (!empty($imports)) {
          foreach (Import::loadMultiple($imports) as $import) {
            self::set($import, self::UNPUBLISH);
          }
        }
        break;

      case 'Drupal\effective_activism\Entity\Import':
        self::set($entity, self::UNPUBLISH);
        $events = \Drupal::entityQuery('event')
          ->condition('import', $entity->id())
          ->execute();
        if (!empty($events)) {
          foreach (Event::loadMultiple($events) as $event) {
            self::unpublish($event);
          }
        }
        break;

      case 'Drupal\effective_activism\Entity\Event':
        self::set($entity, self::UNPUBLISH);
        if (!empty($entity->get('results'))) {
          foreach ($entity->get('results') as $item) {
            self::unpublish($item->entity);
          }
        }
        break;

      case 'Drupal\effective_activism\Entity\Result':
        self::set($entity, self::UNPUBLISH);
        break;

      default:
        // No default unpublish action.
    }
  }

  /**
   * Set a publish state for an entity.
   *
   * @param EntityInterface $entity
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
