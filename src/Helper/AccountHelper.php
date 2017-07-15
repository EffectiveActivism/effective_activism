<?php

namespace Drupal\effective_activism\Helper;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Entity\Group;

/**
 * Helper functions for user accounts.
 */
class AccountHelper {

  /**
   * Checks if user account is manager of the organization.
   *
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization to check.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The user account to check for.
   *
   * @return bool
   *   TRUE if user account is manager, FALSE otherwise.
   */
  public static function isManager(Organization $organization, AccountProxyInterface $account = NULL) {
    if (empty($account)) {
      $account = \Drupal::currentUser();
    }
    $is_manager = FALSE;
    foreach ($organization->get('managers')->getValue() as $entity_reference) {
      if ($entity_reference['target_id'] === $account->id()) {
        $is_manager = TRUE;
        break;
      }
    }
    return $is_manager;
  }

  /**
   * Checks if user account is manager of the group.
   *
   * @param \Drupal\effective_activism\Entity\Group $group
   *   The organization to check.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The user account to check for.
   *
   * @return bool
   *   TRUE if user account is manager, FALSE otherwise.
   */
  public static function isManagerOfGroup(Group $group, AccountProxyInterface $account = NULL) {
    if (empty($account)) {
      $account = \Drupal::currentUser();
    }
    $is_manager = FALSE;

    foreach ($group->organization->entity->get('managers')->getValue() as $entity_reference) {
      if ($entity_reference['target_id'] === $account->id()) {
        $is_manager = TRUE;
        break;
      }
    }
    return $is_manager;
  }

  /**
   * Checks if user account is organizer of the group.
   *
   * @param \Drupal\effective_activism\Entity\Group $group
   *   The group to check.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The user account to check for.
   *
   * @return bool
   *   TRUE if user account is organizer, FALSE otherwise.
   */
  public static function isOrganizer(Group $group, AccountProxyInterface $account = NULL) {
    if (empty($account)) {
      $account = \Drupal::currentUser();
    }
    $is_organizer = FALSE;
    foreach ($group->get('organizers')->getValue() as $entity_reference) {
      if ($entity_reference['target_id'] === $account->id()) {
        $is_organizer = TRUE;
        break;
      }
    }
    return $is_organizer;
  }

  /**
   * Checks if user account is organizer of the group.
   *
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The group to check.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The user account to check for.
   *
   * @return bool
   *   TRUE if user account is organizer, FALSE otherwise.
   */
  public static function isOrganizerOfOrganization(Organization $organization, AccountProxyInterface $account = NULL) {
    if (empty($account)) {
      $account = \Drupal::currentUser();
    }
    $is_organizer = FALSE;
    foreach (OrganizationHelper::getGroups($organization) as $group) {
      if (self::isOrganizer($group, $account)) {
        $is_organizer = TRUE;
        break;
      }
    }
    return $is_organizer;
  }

  /**
   * Get organizations that a user account is manager of.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The user object to check relationship for.
   * @param bool $load_entities
   *   Wether to return fully loaded entities or ids.
   *
   * @return array
   *   An array of organizations that the user account is manager of.
   */
  public static function getManagedOrganizations(AccountProxyInterface $account = NULL, $load_entities = TRUE) {
    if (empty($account)) {
      $account = \Drupal::currentUser();
    }
    $result = \Drupal::entityQuery('organization')
      ->condition('managers', $account->id())
      ->sort('title')
      ->execute();
    return $load_entities ? Organization::loadMultiple($result) : array_values($result);
  }

  /**
   * Get organizations that a user account is manager or organizer of.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The user object to check relationship for.
   * @param bool $load_entities
   *   Wether to return fully loaded entities or ids.
   *
   * @return array
   *   An array of organizations that the user account is manager of.
   */
  public static function getOrganizations(AccountProxyInterface $account = NULL, $load_entities = TRUE) {
    if (empty($account)) {
      $account = \Drupal::currentUser();
    }
    $result = self::getManagedOrganizations($account, FALSE);
    $organized_groups = self::getOrganizedGroups($account);
    foreach ($organized_groups as $group) {
      $result[$group->organization->entity->id()] = $group->organization->entity->id();
    }
    return $load_entities ? Organization::loadMultiple($result) : array_values($result);
  }

  /**
   * Get groups that a user account is manager or organizer of.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The user object to check relationship for.
   * @param bool $load_entities
   *   Wether to return fully loaded entities or ids.
   *
   * @return array
   *   An array of organizations that the user account is manager of.
   */
  public static function getGroups(AccountProxyInterface $account = NULL, $load_entities = TRUE) {
    if (empty($account)) {
      $account = \Drupal::currentUser();
    }
    $result = self::getOrganizedGroups($account, FALSE);
    foreach (self::getManagedOrganizations($account) as $organization) {
      // Include all groups of organizations that the user manages.
      $organization_groups = OrganizationHelper::getGroups($organization, 0, 0, FALSE);
      $result = array_merge($result, $organization_groups);
    }
    return $load_entities ? Group::loadMultiple($result) : array_values($result);
  }

  /**
   * Get groups that a user account is organizer of.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The user object to check relationship for.
   * @param bool $load_entities
   *   Wether to return fully loaded entities or ids.
   *
   * @return array
   *   An array of groups that the user account is organizer of.
   */
  public static function getOrganizedGroups(AccountProxyInterface $account = NULL, $load_entities = TRUE) {
    if (empty($account)) {
      $account = \Drupal::currentUser();
    }
    $result = \Drupal::entityQuery('group')
      ->condition('organizers', $account->id())
      ->sort('title')
      ->execute();
    return $load_entities ? Group::loadMultiple($result) : array_values($result);
  }

}
