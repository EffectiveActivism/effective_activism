<?php

namespace Drupal\effective_activism\Form\Invitation;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Helper\InvitationHelper;

/**
 * Provides an invitation response form.
 */
class InvitationForm extends FormBase {

  const FORM_ID = 'effective_activism_invitation';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return self::FORM_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $invitation = NULL) {
    if (!empty($invitation)) {
      $entity = NULL;
      switch ($invitation->entity_type) {
        case 'organization':
          $entity = Organization::load($invitation->entity_id);
          $form_state->setTemporaryValue('role', 'manager');
          break;

        case 'group':
          $entity = Group::load($invitation->entity_id);
          $form_state->setTemporaryValue('role', 'organizer');
          break;
      }
      $form_state->setTemporaryValue('invitation_id', $invitation->id);
      $form_state->setTemporaryValue('entity', $entity);
      $form_state->setTemporaryValue('email', $invitation->email);
      $form['form'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('You have been invited to an @entity_type', [
          '@entity_type' => $entity->getEntityTypeId(),
        ]),
        '#attributes' => [
          'class' => [
            'invitation',
          ],
        ],
      ];
      $form['form']['invitation'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('You have been invited to join <em>@title</em> as @role. Please accept or decline the invitation.', [
          '@title' => $entity->label(),
          '@role' => $form_state->getTemporaryValue('role'),
        ]) . '</p>',
      ];
      $form['form']['accept'] = [
        '#type' => 'submit',
        '#value' => $this->t('Accept'),
        '#name' => 'accept-invitation',
      ];
      $form['form']['decline'] = [
        '#type' => 'submit',
        '#value' => $this->t('Decline'),
        '#name' => 'decline-invitation',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $invitation_id = $form_state->getTemporaryValue('invitation_id');
    $submit_element = $form_state->getTriggeringElement();
    if ($submit_element['#name'] === 'accept-invitation') {
      $entity = $form_state->getTemporaryValue('entity');
      $user = user_load_by_mail($form_state->getTemporaryValue('email'));
      // Get link to entity default page.
      $url = $entity->urlInfo()->setOptions([
        'attributes' => [
          'target' => '_blank',
        ],
      ]);
      $link = Link::fromTextAndUrl($entity->label(), $url)->toString();
      // Add current user to entity with specified role.
      switch ($entity->getEntityTypeId()) {
        case 'organization':
          $entity->managers[] = $user->id();
          break;

        case 'group':
          $entity->organizers[] = $user->id();
      }
      $entity->save();
      drupal_set_message(t('You are now @role for <em>@link</em>.', [
        '@role' => $form_state->getTemporaryValue('role'),
        '@link' => $link,
      ]));
      // Remove current users invitation.
      InvitationHelper::removeInvition($invitation_id);
    }
    elseif ($submit_element['#name'] === 'decline-invitation') {
      drupal_set_message(t('Invitation declined.'));
      // Remove current users invitation.
      InvitationHelper::removeInvition($invitation_id);
    }
  }

}
