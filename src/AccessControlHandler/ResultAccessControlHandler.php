<?php

namespace Drupal\effective_activism\AccessControlHandler;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Entity\ResultType;

/**
 * Access controller for the Result entity.
 *
 * @see \Drupal\effective_activism\Entity\Result.
 */
class ResultAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessControl::isManager(Organization::load($entity->type->entity->get('organization')), $account);
        }
        else {
          $group_ids = $entity->type->entity->get('groups');
          $groups = empty($group_ids) ? [] : Group::loadMultiple(array_keys($group_ids));
          return AccessControl::isGroupStaff($groups, $account);
        }

      case 'update':
        $group_ids = $entity->type->entity->get('groups');
        $groups = empty($group_ids) ? [] : Group::loadMultiple(array_keys($group_ids));
        return AccessControl::isGroupStaff($groups, $account);

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
    $result_type = empty($entity_bundle) ? NULL : ResultType::load($entity_bundle);
    $group_ids = empty($result_type) ? [] : $result_type->get('groups');
    $groups = empty($group_ids) ? [] : Group::loadMultiple(array_keys($group_ids));
    return AccessControl::isGroupStaff($groups, $account);
  }

}
