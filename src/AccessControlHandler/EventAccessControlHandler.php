<?php

namespace Drupal\effective_activism\AccessControlHandler;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Event entity.
 *
 * @see \Drupal\effective_activism\Entity\Event.
 */
class EventAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessControl::isManager($entity->get('parent')->entity->get('organization')->entity, $account);
        }
        else {
          return AccessControl::isStaff($entity->get('parent')->entity->get('organization')->entity, $account);
        }

      case 'update':
        return AccessControl::isGroupStaff([$entity->get('parent')->entity], $account);

      case 'delete':
        return AccessControl::isManager($entity->get('parent')->entity->get('organization')->entity, $account);
    }
    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessControl::isGroupStaff([Drupal::request()->get('group')], $account);
  }

}
