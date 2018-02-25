<?php

namespace Drupal\effective_activism\AccessControlHandler;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Import entity.
 *
 * @see \Drupal\effective_activism\Entity\Import.
 */
class ImportAccessControlHandler extends EntityAccessControlHandler {

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
          return AccessControl::isGroupStaff([$entity->get('parent')->entity], $account);
        }

      case 'update':
        return AccessControl::isGroupStaff([$entity->get('parent')->entity], $account);

      case 'delete':
        return AccessResult::forbidden();
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
