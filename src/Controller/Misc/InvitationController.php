<?php

namespace Drupal\effective_activism\Controller\Misc;

use Drupal;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Helper\InvitationHelper;
use Drupal\effective_activism\Form\Invitation\InvitationForm;

/**
 * Controller class for contact information.
 */
class InvitationController extends ControllerBase {

  const THEME_ID = 'invitation';

  const CACHE_MAX_AGE = 0;

  /**
   * Returns an array of fields for contact information.
   *
   * @param EntityInterface $entity
   *   The entity to provide contact information for.
   *
   * @return array
   *   A render array.
   */
  public function view() {
    $content['#theme'] = self::THEME_ID;
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
}
