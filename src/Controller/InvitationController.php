<?php

namespace Drupal\effective_activism\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Helper\AccountHelper;
use Drupal\effective_activism\Helper\InvitationHelper;

/**
 * Controller class for invitations.
 */
class InvitationController extends ControllerBase {

  /**
   * Removes an invitation.
   *
   * @param stdClass $invitation
   *   An invitation object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect back to the entity of the invitation.
   */
  public function remove($invitation = NULL) {
    if (!empty($invitation)) {
      // Verify that user may remove invitation.
      $access = FALSE;
      switch ($invitation->entity_type) {
        case 'organization':
          $organization = Organization::load($invitation->entity_id);
          if ($organization && AccountHelper::isManager($organization, Drupal::currentUser())) {
            $access = TRUE;
          }
          break;

        case 'group':
          $group = Group::load($invitation->entity_id);
          if ($group && (AccountHelper::isOrganizer($group, Drupal::currentUser()) || AccountHelper::isManagerOfGroup($group, Drupal::currentUser()))) {
            $access = TRUE;
          }
          break;
      }
      if ($access) {
        $result = InvitationHelper::removeInvition($invitation->id);
        if ($result > 0) {
          drupal_set_message(t('Invitation removed.'));
        }
      }
    }
    return $this->redirect(sprintf('entity.%s.edit_form', $invitation->entity_type), [$invitation->entity_type => $invitation->entity_id]);
  }

}
