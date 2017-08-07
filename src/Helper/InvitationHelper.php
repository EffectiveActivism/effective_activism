<?php

namespace Drupal\effective_activism\Helper;

use Drupal;
use Drupal\Core\Entity\EntityInterface;
use Drupal\effective_activism\Constant;
use Exception;

/**
 * Provides helper functions for organization and group invitations.
 */
class InvitationHelper {

  const STATUS_ALREADY_MANAGER = 0;
  const STATUS_ALREADY_ORGANIZER = 1;
  const STATUS_ALREADY_INVITED = 2;
  const STATUS_NEW_USER = 3;
  const STATUS_EXISTING_USER = 4;

  /**
   * Check if e-mail address is invited.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The organization id to check for.
   * @param string $email
   *   The email to check for.
   *
   * @return int
   *   A status code.
   */
  public static function isInvited(EntityInterface $entity, $email) {
    $status = NULL;
    $account = user_load_by_mail($email);
    if ($account !== FALSE) {
      // Check that user is already part of the entity.
      switch ($entity->getEntityTypeId()) {
        case 'organization':
          if (in_array(['target_id' => $account->id()], $entity->get('managers')->getValue())) {
            return self::STATUS_ALREADY_MANAGER;
          }
          break;

        case 'group':
          if (in_array(['target_id' => $account->id()], $entity->get('organizers')->getValue())) {
            return self::STATUS_ALREADY_ORGANIZER;
          }
          break;
      }
    }
    // Check that user isn't already invited.
    $result = db_select(Constant::INVITATION_TABLE, 'invitation')
      ->fields('invitation')
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id())
      ->condition('email', $email)
      ->countQuery()
      ->execute()
      ->fetchField();
    if ($result > 0) {
      return self::STATUS_ALREADY_INVITED;
    }
    // Check if user exists.
    return ($account === FALSE) ? self::STATUS_NEW_USER : self::STATUS_EXISTING_USER;
  }

  /**
   * Add an invitation for a user.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to add invitation for.
   * @param string $email
   *   The email address to invite.
   *
   * @return bool|int
   *   The id of the invitation or FALSE if the operation failed.
   */
  public static function addInvition(EntityInterface $entity, $email) {
    try {
      return db_insert(Constant::INVITATION_TABLE)
        ->fields([
          'created' => time(),
          'entity_type' => $entity->getEntityTypeId(),
          'entity_id' => $entity->id(),
          'email' => $email,
        ])
        ->execute();
    }
    catch (Exception $e) {
      Drupal::logger('effective_activism')->error('Failed to add invitation.');
      return FALSE;
    }
  }

  /**
   * Delete an invitation for a user.
   *
   * @param int $id
   *   The invitation id.
   *
   * @return bool|int
   *   The number of deleted invitations or FALSE if the operation failed.
   */
  public static function removeInvition($id) {
    try {
      return db_delete(Constant::INVITATION_TABLE)
        ->condition('id', $id)
        ->execute();
    }
    catch (Exception $e) {
      Drupal::logger('effective_activism')->error('Failed to delete invitation.');
      return FALSE;
    }
  }

  /**
   * Retrieve an invitation.
   *
   * @param int $id
   *   The id of the invitation.
   *
   * @return bool|array
   *   A list of invitations or FALSE if the operation failed.
   */
  public static function getInvitation($id) {
    try {
      return db_select(Constant::INVITATION_TABLE, 'invitation')
        ->fields('invitation')
        ->condition('id', $id)
        ->execute()
        ->fetchAll();
    }
    catch (Exception $e) {
      Drupal::logger('effective_activism')->error('Failed to retrieve invitation.');
      return FALSE;
    }
  }

  /**
   * Retrieves a list of invitations identified by e-mail.
   *
   * @param string $email
   *   The email to check invitations for.
   *
   * @return bool|array
   *   A list of invitations or FALSE if the operation failed.
   */
  public static function getInvitations($email) {
    try {
      return db_select(Constant::INVITATION_TABLE, 'invitation')
        ->fields('invitation')
        ->condition('email', $email)
        ->execute()
        ->fetchAll();
    }
    catch (Exception $e) {
      Drupal::logger('effective_activism')->error('Failed to retrieve invitations.');
      return FALSE;
    }
  }

  /**
   * Retrieves a list of invitations identified by entity relationship.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity relationship.
   *
   * @return bool|array
   *   A list of invitations or FALSE if the operation failed.
   */
  public static function getInvitationsByEntity(EntityInterface $entity) {
    try {
      return db_select(Constant::INVITATION_TABLE, 'invitation')
        ->fields('invitation')
        ->condition('entity_type', $entity->getEntityTypeId())
        ->condition('entity_id', $entity->id())
        ->execute()
        ->fetchAll();
    }
    catch (Exception $e) {
      Drupal::logger('effective_activism')->error('Failed to retrieve invitations.');
      return FALSE;
    }
  }

}
