<?php

namespace Drupal\effective_activism\Form;

use Drupal;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\AccessControlHandler\AccessControl;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Entity\ResultType;
use Drupal\effective_activism\Helper\InvitationHelper;
use Drupal\effective_activism\Helper\OrganizationHelper;
use Drupal\effective_activism\Helper\PathHelper;
use ReflectionClass;

/**
 * Form controller for Group edit forms.
 *
 * @ingroup effective_activism
 */
class GroupForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Organization $organization = NULL, Group $group = NULL) {
    /* @var $entity \Drupal\effective_activism\Entity\Group */
    $form = parent::buildForm($form, $form_state);
    $form['#theme'] = (new ReflectionClass($this))->getShortName();
    $entity = $this->entity;
    // Set values from path.
    $form['organization']['widget'][0]['target_id']['#default_value'] = $organization;
    // Hide fields.
    $form['user_id']['#attributes']['class'][] = 'hidden';
    $form['revision_log_message']['#attributes']['class'][] = 'hidden';
    // Add result type selection for this group.
    $form['result_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Result types'),
      '#default_value' => [],
      '#options' => [],
    ];
    if ($entity->isNew()) {
      $form['result_types']['#description'] = $this->t('Result types will be available once this group is created.');
    }
    else {
      $form['result_types']['#description'] = $this->t('Result types available for this group.');
      $selected_result_types = [];
      $form['result_types']['#default_value'] = empty($selected_result_types) ? [] : $selected_result_types;
      foreach (OrganizationHelper::getResultTypes($organization) as $result_type) {
        $form['result_types']['#options'][$result_type->id()] = sprintf('%s<br><small><em>%s</em></small>', $result_type->label(), $result_type->description);
        if (in_array($entity->id(), $result_type->groups)) {
          $selected_result_types[$result_type->id()] = $result_type->id();
        }
      }
      $form_state->setTemporaryValue('old_result_type_selection', $selected_result_types);
      $form['result_types']['#default_value'] = $selected_result_types;
    }
    // If the group is saved, populate active invitations.
    $form['#invitations'] = $entity->isNew() ? [] : InvitationHelper::getInvitationsByEntity($entity);
    // Only allow managers to see some elements and only for existing groups.
    if ($entity->isNew() || AccessControl::isManager($entity->organization->entity, Drupal::currentUser())->isForbidden()) {
      $form['result_types']['#access'] = FALSE;
      $form['organizers']['#access'] = FALSE;
      $form['invitations']['#access'] = FALSE;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $entity = $this->entity;
    $title = $form_state->getValue('title')[0]['value'];
    $existing_group = PathHelper::loadGroupBySlug(PathHelper::transliterate($title), Drupal::request()->get('organization'));
    if (
      !empty($existing_group) &&
      ($entity->isNew() || $existing_group->id() !== $entity->id())
    ) {
      $form_state->setErrorByName('title', $this->t('The title you have chosen is in use. Please choose another one.'));
    }
    if (PathHelper::transliterate($title) === 'add') {
      $form_state->setErrorByName('title', $this->t('This title is not allowed. Please choose another one.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->setNewRevision();
    $status = parent::save($form, $form_state);
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label group.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        // Check if there are changes to result types and update accordingly.
        if ($form_state->getTemporaryValue('old_result_type_selection') != array_filter($form_state->getValue('result_types'), function ($element) {
          return ($element !== 0);
        })) {
          foreach ($form_state->getValue('result_types') as $result_type_id => $enabled) {
            $result_type = ResultType::load($result_type_id);
            $groups = array_keys($result_type->groups);
            if ($enabled !== 0 && !in_array($entity->id(), $groups)) {
              $groups[] = $entity->id();
            }
            elseif ($enabled === 0) {
              $groups = array_diff($groups, [$entity->id()]);
            }
            if (array_keys($result_type->groups) !== $groups) {
              $result_type->groups = array_combine($groups, $groups);
              $result_type->save();
            }
          }
        }
        drupal_set_message($this->t('Saved the %label group.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.group.canonical', [
      'organization' => PathHelper::transliterate(Drupal::request()->get('organization')->label()),
      'group' => PathHelper::transliterate($entity->label()),
    ]);
  }

}
