<?php

namespace Drupal\effective_activism\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\effective_activism\AccessControlHandler\AccessControl;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Form\InvitationForm;
use Drupal\effective_activism\Helper\InvitationHelper;
use Drupal\effective_activism\Helper\PathHelper;
use ReflectionClass;

/**
 * Controller class for invitations.
 */
class InvitationController extends ControllerBase {

  const CACHE_MAX_AGE = 0;

  /**
   * Returns a render array for the overview page.
   *
   * @return array
   *   A render array.
   */
  public function overview(array $invitations) {
    $content['#theme'] = (new ReflectionClass($this))->getShortName();
    $content['#storage']['invitations'] = $invitations;
    $content['#cache'] = [
      'max-age' => self::CACHE_MAX_AGE,
      'tags' => self::CACHE_TAGS,
    ];
    return $content;
  }

  /**
   * Returns an array of fields for contact information.
   *
   * @return array
   *   A render array.
   */
  public function view() {
    $content['#theme'] = (new ReflectionClass($this))->getShortName();
    $content['#cache'] = [
      'max-age' => self::CACHE_MAX_AGE,
    ];
    $content['#storage']['invitations'] = [];
    // Check if user is invited to any groups.
    $user = Drupal::currentUser();
    if ($user->isAuthenticated()) {
      // Look up user e-mail address in table.
      // If user has a pending invitation, display invitation form.
      $email = $user->getEmail();
      $invitations = InvitationHelper::getInvitations($email);
      if (!empty($invitations)) {
        foreach ($invitations as $invitation) {
          $content['#storage']['invitations'][] = Drupal::formBuilder()->getForm(InvitationForm::class, $invitation);
        }
      }
    }
    return $content;
  }

  /**
   * Removes an invitation.
   *
   * @param object $invitation
   *   An invitation object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect back to the entity of the invitation.
   */
  public function remove($invitation = NULL) {
    $redirect_parameters = [];
    if (!empty($invitation)) {
      // Verify that user may remove invitation.
      $access = FALSE;
      switch ($invitation->entity_type) {
        case 'organization':
          $organization = Organization::load($invitation->entity_id);
          if ($organization && AccessControl::isManager($organization)->isAllowed()) {
            $access = TRUE;
            $redirect_parameters = [
              'organization' => PathHelper::transliterate($organization->label()),
            ];
          }
          break;

        case 'group':
          $group = Group::load($invitation->entity_id);
          if ($group && (AccessControl::isOrganizer($group)->isAllowed() || AccessControl::isManager($group->organization->entity)->isAllowed())) {
            $access = TRUE;
            $redirect_parameters = [
              'organization' => PathHelper::transliterate(Drupal::request()->get('organization')->label()),
              'group' => PathHelper::transliterate($group->label()),
            ];
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
    return $this->redirect(sprintf('entity.%s.edit_form', $invitation->entity_type), $redirect_parameters);
  }

}
