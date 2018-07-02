<?php

namespace Drupal\effective_activism\AccessControlHandler;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Export entity.
 *
 * @see \Drupal\effective_activism\Entity\Export.
 */
class ExportAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        if ($entity->get('parent')->isEmpty()) {
          return AccessControl::isManager($entity->get('organization')->entity, $account);
        }
        else {
          return AccessControl::isGroupStaff([$entity->get('parent')->entity], $account);
        }

      case 'update':
        return AccessResult::forbidden();

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
    if (Drupal::request()->get('group') === NULL) {
      return AccessControl::isManager(Drupal::request()->get('organization'), $account);
    }
    else {
      return AccessControl::isGroupStaff([Drupal::request()->get('group')], $account);
    }
  }

}
