<?php

namespace Drupal\effective_activism\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Helper\InvitationHelper;
use Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormComplex;

/**
 * Plugin implementation of the organization manager widget.
 *
 * @FieldWidget(
 *   id = "inline_manager_invitation",
 *   label = @Translation("Manager invitation widget"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = true
 * )
 */
class InlineManagerInvitationWidget extends InlineEntityFormComplex {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    // Remove 'add existing user' button.
    // This step is necessary because 'allow_existing' must be TRUE in order for
    // entities not to be deleted when their reference is removed.
    unset($element['actions']['ief_add_existing']);
    // Determine the wrapper ID for the entire element.
    $wrapper = 'inline-entity-form-' . $this->getIefId();
    // Add custom button to add people from the organization.
    $element['actions']['invite_manager'] = [
      '#type' => 'submit',
      '#value' => $this->t('Invite new manager'),
      '#name' => 'ief-' . $this->getIefId() . '-invite',
      '#ajax' => [
        'callback' => 'inline_entity_form_get_element',
        'wrapper' => $wrapper,
      ],
      '#attributes' => [
        'class' => ['invite_new'],
      ],
      '#submit' => ['inline_entity_form_open_form'],
      '#ief_form' => 'ief_invite',
    ];
    // Add invitation form if organization id is set.
    if ($form_state->get(['inline_entity_form', $this->getIefId(), 'form']) === 'ief_invite') {
      $element['form'] = [
        '#type' => 'fieldset',
        '#attributes' => ['class' => ['ief-form', 'ief-form-bottom']],
        '#description' => t('Type in the e-mail address of the person you would like to invite to the organization as manager.'),
      ];
      $element['form']['#title'] = t('Invite a manager');
      $element['form']['invite_email_address'] = [
        '#type' => 'email',
        '#title' => t('E-mail address'),
        '#description' => t('Enter the e-mail address of the person you would like to invite.'),
        '#required' => TRUE,
        '#maxlength' => 255,
        '#placeholder' => t('E-mail address'),
        '#title_display' => 'hidden',
      ];
      $element['form']['invite'] = [
        '#type' => 'submit',
        '#value' => t('Invite manager'),
        '#name' => 'ief-reference-invite-' . $this->getIefId(),
        '#limit_validation_errors' => [
          ['managers', 'form', 'invite_email_address'],
        ],
        '#ajax' => [
          'callback' => ['\Drupal\effective_activism\Plugin\Field\FieldWidget\InlineManagerInvitationWidget', 'invite'],
          'wrapper' => 'inline-entity-form-' . $this->getIefId(),
        ],
        '#submit' => [['\Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormComplex', 'closeForm']],
        '#attributes' => [
          'class' => ['invite'],
        ],
      ];
      $element['form']['cancel'] = [
        '#type' => 'submit',
        '#value' => t('Cancel'),
        '#name' => 'ief-reference-cancel-' . $this->getIefId(),
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => 'inline_entity_form_get_element',
          'wrapper' => 'inline-entity-form-' . $this->getIefId(),
        ],
        '#submit' => [['\Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormComplex', 'closeForm']],
        '#attributes' => [
          'class' => ['cancel'],
        ],
      ];
      // Hide 'Invite new manager' button.
      unset($element['actions']['invite_manager']);
    }
    return $element;
  }

  /**
   * Submits the form for inviting users.
   *
   * @param array $form
   *   The entity form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the form.
   */
  public static function invite(array $form, FormStateInterface $form_state) {
    $entity_id = $form_state->getTemporaryValue('entity_id');
    if (!empty($entity_id)) {
      $entity = Organization::load($entity_id);
      $email = $form_state->getValue([
        'managers',
        'form',
        'invite_email_address',
      ]);
      $status = NULL;
      if (!empty($email)) {
        $status = InvitationHelper::isInvited($entity, $email);
        // Add invitation.
        if ($status === InvitationHelper::STATUS_NEW_USER || $status === InvitationHelper::STATUS_EXISTING_USER) {
          InvitationHelper::addInvition($entity, $email);
        }
        // Display message of invitation status.
        switch ($status) {
          case InvitationHelper::STATUS_ALREADY_MANAGER:
            drupal_set_message(t('The user is already a manager of this organization.'), 'warning');
            break;

          case InvitationHelper::STATUS_ALREADY_INVITED:
            drupal_set_message(t('The user is already invited to this organization.'), 'warning');
            break;

          case InvitationHelper::STATUS_NEW_USER:
            drupal_set_message(t('An invitation to join your organization as manager will be shown for the user with the e-mail address <em>@email_address</em> once the person registers with the site.', ['@email_address' => $email]));
            break;

          case InvitationHelper::STATUS_EXISTING_USER:
            drupal_set_message(t('An invitation to join your organization as manager will be shown for the user with the e-mail address <em>@email_address</em> next time the user logs in.', ['@email_address' => $email]));
            break;
        }
      }
    }
    return $form['managers'];
  }

}
