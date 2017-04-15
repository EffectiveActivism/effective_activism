<?php

namespace Drupal\effective_activism\Helper\AccessControlHandler;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Permission\Permission;

/**
 * Access controller for the ResultType entity.
 *
 * @see \Drupal\effective_activism\Entity\ResultType.
 */
class ResultTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        $group_ids = array_keys($entity->get('groups'));
        $groups = empty($group_ids) ? [] : Group::loadMultiple($group_ids);
        return Permission::isGroupStaff($groups, $account);

      case 'update':
        return Permission::isManager(Organization::load($entity->get('organization')), $account);

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
