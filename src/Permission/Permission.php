<?php

namespace Drupal\effective_activism\Permission;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Session\AccountInterface;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Organization;

/**
 * Provides functions to manage permissions.
 */
class Permission {

  /**
   * Grants access if the user is manager of the organization.
   *
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization to check relationship for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check a permission.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Returns an access result.
   */
  public static function isManager(Organization $organization, AccountInterface $account = NULL) {
    if (!isset($account)) {
      $account = \Drupal::currentUser();
    }
    if ((int) $account->id() === 1) {
      return new AccessResultAllowed();
    }
    if (in_array(['target_id' => $account->id()], $organization->get('managers')->getValue())) {
      return new AccessResultAllowed();
    }
    else {
      return new AccessResultForbidden();
    }
  }

  /**
   * Grants access if the user is organizer of the group.
   *
   * @param \Drupal\effective_activism\Entity\Group $group
   *   The group to check relationship for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check a permission.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Returns an access result.
   */
  public static function isOrganizer(Group $group, AccountInterface $account = NULL) {
    if (!isset($account)) {
      $account = \Drupal::currentUser();
    }
    if ((int) $account->id() === 1) {
      return new AccessResultAllowed();
    }
    if (in_array(['target_id' => $account->id()], $group->get('organizers')->getValue())) {
      return new AccessResultAllowed();
    }
    else {
      return new AccessResultForbidden();
    }
  }

  /**
   * Grants access if the user is staff.
   *
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization to check relationship for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check a permission.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Returns an access result.
   */
  public static function isStaff(Organization $organization, AccountInterface $account = NULL) {
    if (!isset($account)) {
      $account = \Drupal::currentUser();
    }
    if ((int) $account->id() === 1) {
      return new AccessResultAllowed();
    }
    if (in_array(['target_id' => $account->id()], $organization->get('managers')->getValue())) {
      return new AccessResultAllowed();
    }
    // Check for each group of the organization.
    $group_ids = \Drupal::entityQuery('group')->condition('organization', $organization->id())->execute();
    $groups = Group::loadMultiple($group_ids);
    foreach ($groups as $group) {
      if (in_array(['target_id' => $account->id()], $group->get('organizers')->getValue())) {
        return new AccessResultAllowed();
      }
    }
    return new AccessResultForbidden();
  }

  /**
   * Grants access if the user is staff of the groups.
   *
   * @param array $groups
   *   The groups to check relationship for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check a permission.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Returns an access result.
   */
  public static function isGroupStaff(array $groups, AccountInterface $account = NULL) {
    if (!isset($account)) {
      $account = \Drupal::currentUser();
    }
    if ((int) $account->id() === 1) {
      return new AccessResultAllowed();
    }
    if (!empty($groups)) {
      foreach ($groups as $group) {
        if (in_array(['target_id' => $account->id()], $group->get('organization')->entity->get('managers')->getValue())) {
          return new AccessResultAllowed();
        }
        if (in_array(['target_id' => $account->id()], $group->get('organizers')->getValue())) {
          return new AccessResultAllowed();
        }
      }
    }
    return new AccessResultForbidden();
  }

  /**
   * Grants access if the user is staff of any organization.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check a permission.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Returns an access result.
   */
  public static function isAnyStaff(AccountInterface $account = NULL) {
    if (!isset($account)) {
      $account = \Drupal::currentUser();
    }
    if ((int) $account->id() === 1) {
      return new AccessResultAllowed();
    }
    $organizations = \Drupal::entityQuery('organization')
      ->condition('managers', $account->id())
      ->count()
      ->execute();
    if ($organizations > 0) {
      return new AccessResultAllowed();
    }
    $groups = \Drupal::entityQuery('group')
      ->condition('organizers', $account->id())
      ->count()
      ->execute();
    if ($groups > 0) {
      return new AccessResultAllowed();
    }
    else {
      return new AccessResultForbidden();
    }
  }

  /**
   * Grants acces if the user is not staff of any organization.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check a permission.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Returns an access result.
   */
  public static function isNotAnyStaff(AccountInterface $account = NULL) {
    if (!isset($account)) {
      $account = \Drupal::currentUser();
    }
    $organizations = \Drupal::entityQuery('organization')
      ->condition('managers', $account->id())
      ->count()
      ->execute();
    if ($organizations > 0) {
      return new AccessResultForbidden();
    }
    $groups = \Drupal::entityQuery('group')
      ->condition('organizers', $account->id())
      ->count()
      ->execute();
    if ($groups > 0) {
      return new AccessResultForbidden();
    }
    else {
      return new AccessResultAllowed();
    }
  }

  /**
   * Grants access if the user is manager of any organizations.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check a permission.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Returns an access result.
   */
  public static function isAnyManager(AccountInterface $account = NULL) {
    if (!isset($account)) {
      $account = \Drupal::currentUser();
    }
    if ((int) $account->id() === 1) {
      return new AccessResultAllowed();
    }
    $organizations = \Drupal::entityQuery('organization')
      ->condition('managers', $account->id())
      ->count()
      ->execute();
    if ($organizations > 0) {
      return new AccessResultAllowed();
    }
    else {
      return new AccessResultForbidden();
    }
  }

  /**
   * Grants access if the user is organizer of any groups.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check a permission.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Returns an access result.
   */
  public static function isAnyOrganizer(AccountInterface $account = NULL) {
    if (!isset($account)) {
      $account = \Drupal::currentUser();
    }
    if ((int) $account->id() === 1) {
      return new AccessResultAllowed();
    }
    $groups = \Drupal::entityQuery('group')
      ->condition('organizers', $account->id())
      ->count()
      ->execute();
    if ($groups > 0) {
      return new AccessResultAllowed();
    }
    else {
      return new AccessResultForbidden();
    }
  }

}
