<?php

namespace Drupal\effective_activism\Form\Filter;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Helper\FilterHelper;

/**
 * Form controller for Filter edit forms.
 *
 * @ingroup effective_activism
 */
class FilterForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\effective_activism\Entity\Filter */
    $form = parent::buildForm($form, $form_state);
    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => FALSE,
        '#weight' => 10,
      ];
    }
    // Get result types.
    $available_result_types = array_map(function ($result_type) {
      return $result_type->label();
    }, OrganizationHelper::getResultTypes($organization));
    $form['result_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Result types'),
      '#default_value' => $form_state->getValue('result_types'),
      '#options' => $available_result_types,
      '#description' => $this->t('Available result types.'),
    ];
    $entity = $this->entity;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;
    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();
      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime(REQUEST_TIME);
      $entity->setRevisionUserId(\Drupal::currentUser()->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }
    $status = parent::save($form, $form_state);
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label filter.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label filter.', [
          '%label' => $entity->label(),
        ]));
    }
    dpm('Events matching filter: ' . count(FilterHelper::getEvents($entity)));
    $form_state->setRedirect('entity.filter.canonical', ['filter' => $entity->id()]);
  }

}
