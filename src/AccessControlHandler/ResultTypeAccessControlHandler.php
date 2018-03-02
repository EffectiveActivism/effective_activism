<?php

namespace Drupal\effective_activism\AccessControlHandler;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Organization;

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
        return AccessControl::isGroupStaff($groups, $account);

      case 'update':
        return AccessControl::isManager(Organization::load($entity->get('organization')), $account);

      case 'delete':
        return AccessControl::isManager(Organization::load($entity->get('organization')), $account);
    }
    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessControl::isManager(Drupal::request()->get('organization'), $account);
  }

}
