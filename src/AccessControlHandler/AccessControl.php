<?php

namespace Drupal\effective_activism\AccessControlHandler;

use Drupal;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Helper\PathHelper;

/**
 * Provides functions to manage access control.
 */
class AccessControl {

  /**
   * Custom access callback for routes to check for organization staff.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The matched route.
   * @param \Drupal\Core\Routing\RouteMatchInterface $account
   *   The account requesting access.
   *
   * @return bool
   *   Returns an access result.
   */
  public static function fromRouteIsStaff(RouteMatchInterface $route_match, AccountInterface $account) {
    $parameter_bag = $route_match->getParameters();
    $organization = $parameter_bag->get(Constant::ENTITY_ORGANIZATION);
    return self::isStaff($organization, $account);
  }

  /**
   * Custom access callback for routes to check for managers.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The matched route.
   * @param \Drupal\Core\Routing\RouteMatchInterface $account
   *   The account requesting access.
   *
   * @return bool
   *   Returns an access result.
   */
  public static function fromRouteIsManager(RouteMatchInterface $route_match, AccountInterface $account) {
    $parameter_bag = $route_match->getParameters();
    $organization = $parameter_bag->get(Constant::ENTITY_ORGANIZATION);
    return self::isManager($organization, $account);
  }

  /**
   * Custom access callback for routes to check for group staff.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The matched route.
   * @param \Drupal\Core\Routing\RouteMatchInterface $account
   *   The account requesting access.
   *
   * @return bool
   *   Returns an access result.
   */
  public static function fromRouteIsGroupStaff(RouteMatchInterface $route_match, AccountInterface $account) {
    $parameter_bag = $route_match->getParameters();
    $group = $parameter_bag->get(Constant::ENTITY_GROUP);
    return self::isGroupStaff([$group], $account);
  }

  /**
   * Custom access callback for routes to check for any staff.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The matched route.
   * @param \Drupal\Core\Routing\RouteMatchInterface $account
   *   The account requesting access.
   *
   * @return bool
   *   Returns an access result.
   */
  public static function fromRouteIsAnyStaff(RouteMatchInterface $route_match, AccountInterface $account) {
    $parameter_bag = $route_match->getParameters();
    return self::isAnyStaff($account);
  }

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
      $account = Drupal::currentUser();
    }
    if ((int) $account->id() === 1) {
      return new AccessResultAllowed();
    }
    foreach ($organization->get('managers')->getValue() as $manager) {
      if ($manager['target_id'] === $account->id()) {
        return new AccessResultAllowed();
      }
    }
    return new AccessResultForbidden();
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
      $account = Drupal::currentUser();
    }
    if ((int) $account->id() === 1) {
      return new AccessResultAllowed();
    }
    foreach ($group->get('organizers')->getValue() as $organizer) {
      if ($organizer['target_id'] === $account->id()) {
        return new AccessResultAllowed();
      }
    }
    return new AccessResultForbidden();
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
      $account = Drupal::currentUser();
    }
    if ((int) $account->id() === 1) {
      return new AccessResultAllowed();
    }
    foreach ($organization->get('managers')->getValue() as $manager) {
      if ($manager['target_id'] === $account->id()) {
        return new AccessResultAllowed();
      }
    }
    // Check for each group of the organization.
    $group_ids = Drupal::entityQuery('group')->condition('organization', $organization->id())->execute();
    $groups = Group::loadMultiple($group_ids);
    foreach ($groups as $group) {
      foreach ($group->get('organizers')->getValue() as $organizer) {
        if ($organizer['target_id'] === $account->id()) {
          return new AccessResultAllowed();
        }
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
      $account = Drupal::currentUser();
    }
    if ((int) $account->id() === 1) {
      return new AccessResultAllowed();
    }
    if (!empty($groups)) {
      foreach ($groups as $group) {
        foreach ($group->get('organization')->entity->get('managers')->getValue() as $manager) {
          if ($manager['target_id'] === $account->id()) {
            return new AccessResultAllowed();
          }
        }
        foreach ($group->get('organizers')->getValue() as $organizer) {
          if ($organizer['target_id'] === $account->id()) {
            return new AccessResultAllowed();
          }
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
      $account = Drupal::currentUser();
    }
    if ((int) $account->id() === 1) {
      return new AccessResultAllowed();
    }
    $organizations = Drupal::entityQuery('organization')
      ->condition('managers', $account->id())
      ->count()
      ->execute();
    if ($organizations > 0) {
      return new AccessResultAllowed();
    }
    $groups = Drupal::entityQuery('group')
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
      $account = Drupal::currentUser();
    }
    $organizations = Drupal::entityQuery('organization')
      ->condition('managers', $account->id())
      ->count()
      ->execute();
    if ($organizations > 0) {
      return new AccessResultForbidden();
    }
    $groups = Drupal::entityQuery('group')
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
      $account = Drupal::currentUser();
    }
    if ((int) $account->id() === 1) {
      return new AccessResultAllowed();
    }
    $organizations = Drupal::entityQuery('organization')
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
      $account = Drupal::currentUser();
    }
    if ((int) $account->id() === 1) {
      return new AccessResultAllowed();
    }
    $groups = Drupal::entityQuery('group')
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
