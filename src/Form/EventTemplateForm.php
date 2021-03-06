<?php

namespace Drupal\effective_activism\Form;

use DateTimeZone;
use Drupal;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Helper\PathHelper;
use ReflectionClass;

/**
 * Form controller for event template edit forms.
 *
 * @ingroup effective_activism
 */
class EventTemplateForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Organization $organization = NULL, Group $group = NULL) {
    /* @var $entity \Drupal\effective_activism\Entity\EventTemplate */
    $form = parent::buildForm($form, $form_state);
    $form['#theme'] = (new ReflectionClass($this))->getShortName();
    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => FALSE,
        '#weight' => 10,
      ];
    }
    $entity = $this->entity;
    // Set values from path.
    $form['organization']['widget'][0]['target_id']['#default_value'] = $organization;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Event start date must be older or equal to end date.
    if (
      !empty($form_state->getValue('event_start_date')[0]['value']) &&
      !empty($form_state->getValue('event_end_date')[0]['value'])
    ) {
      $start_date = new DrupalDateTime($form_state->getValue('event_start_date')[0]['value'], new DateTimezone(DATETIME_STORAGE_TIMEZONE));
      $end_date = new DrupalDateTime($form_state->getValue('event_end_date')[0]['value'], new DateTimezone(DATETIME_STORAGE_TIMEZONE));
      if (
        !$start_date->hasErrors() &&
        !$end_date->hasErrors() &&
        $start_date->format('U') > $end_date->format('U')
      ) {
        $form_state->setErrorByName('event_end_date', $this->t('End date must be later than start date.'));
      }
    }
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
      $entity->setRevisionUserId(Drupal::currentUser()->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }
    $status = parent::save($form, $form_state);
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label event template.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label event template.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.event_template.canonical', [
      'organization' => PathHelper::transliterate($entity->organization->entity->label()),
      'event_template' => $entity->id(),
    ]);
  }

}
