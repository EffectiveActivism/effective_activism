<?php

namespace Drupal\effective_activism\Helper\AccessControlHandler;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\effective_activism\Permission\Permission;

/**
 * Access controller for the Filter entity.
 *
 * @see \Drupal\effective_activism\Entity\Filter.
 */
class FilterAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return Permission::isManager($entity->get('organization')->entity, $account);
        }
        else {
          return Permission::isStaff($entity->get('organization')->entity, $account);
        }

      case 'update':
        return Permission::isManager($entity->get('organization')->entity, $account);

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
    return Permission::isAnyManager($account);
  }

}
