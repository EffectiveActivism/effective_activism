<?php

namespace Drupal\effective_activism\Form\Group;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Entity\ResultType;
use Drupal\effective_activism\Helper\OrganizationHelper;

/**
 * Form controller for Group edit forms.
 *
 * @ingroup effective_activism
 */
class GroupForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\effective_activism\Entity\Group */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;
    // Hide fields.
    $form['user_id']['#attributes']['class'][] = 'hidden';
    $form['revision_log_message']['#attributes']['class'][] = 'hidden';
    // Set group entity id.
    $form_state->setTemporaryValue('entity_id', $entity->id());
    // Add result type selection for this group.
    $form['result_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Result types'),
      '#description' => $this->t('Result types will be available once this group is created.'),
      '#default_value' => [],
      '#options' => [],
    ];
    // If the group has an organization, populate available result types.
    if (isset($this->entity->organization->entity)) {
      $form['result_types']['#description'] = $this->t('Result types available for this group.');
      $selected_result_types = [];
      $form['result_types']['#default_value'] = empty($selected_result_types) ? [] : $selected_result_types;
      foreach (OrganizationHelper::getResultTypes($this->entity->organization->entity) as $result_type) {
        $form['result_types']['#options'][$result_type->id()] = sprintf('%s<br><small><em>%s</em></small>', $result_type->label(), $result_type->description);
        if (in_array($entity->id(), $result_type->groups)) {
          $selected_result_types[$result_type->id()] = $result_type->id();
        }
      }
      $form_state->setTemporaryValue('old_result_type_selection', $selected_result_types);
      $form['result_types']['#default_value'] = $selected_result_types;
    }
    return $form;
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
        if ($form_state->getTemporaryValue('old_result_type_selection') != array_filter($form_state->getValue('result_types'), function($element) {
          return ($element !== 0);
        })) {
          foreach ($form_state->getValue('result_types') as $result_type_id => $enabled) {
            $result_type = ResultType::load($result_type_id);
            $groups = $result_type->groups;
            if ($enabled !== 0 && !in_array($entity->id(), $groups)) {
              $groups[] = $entity->id();
            }
            elseif ($enabled === 0) {
              $groups = array_diff($groups, [$entity->id()]);
            }
            if ($result_type->groups !== $groups) {
              $result_type->groups = $groups;
              $result_type->save();
            }
          }
        }
        drupal_set_message($this->t('Saved the %label group.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.group.canonical', ['group' => $entity->id()]);
  }

}
