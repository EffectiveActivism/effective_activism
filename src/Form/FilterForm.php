<?php

namespace Drupal\effective_activism\Form;

use DateTimeZone;
use Drupal;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Entity\Filter;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Helper\OrganizationHelper;
use Drupal\effective_activism\Helper\PathHelper;
use ReflectionClass;

/**
 * Form controller for Filter edit forms.
 *
 * @ingroup effective_activism
 */
class FilterForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Organization $organization = NULL, Filter $filter = NULL) {
    /* @var $entity \Drupal\effective_activism\Entity\Filter */
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
    // Restrict event templates to those belonging to organization.
    $organization_templates = array_reduce(OrganizationHelper::getEventTemplates($organization), function ($result, $event_template) {
      if ($event_template->isPublished()) {
        $result[] = $event_template->id();
      }
      return $result;
    });
    $organization_templates = $organization_templates === NULL ? [] : $organization_templates;
    foreach ($form['event_templates']['widget']['#options'] as $key => $value) {
      if ($key === '_none') {
        continue;
      }
      elseif (!in_array($key, $organization_templates)) {
        unset($form['event_templates']['widget']['#options'][$key]);
      }
    }
    asort($form['event_templates']['widget']['#options']);
    // Restrict result types to those belonging to organization.
    $organization_result_types = OrganizationHelper::getResultTypes($organization, FALSE);
    foreach ($form['result_types']['widget']['#options'] as $key => $value) {
      if ($key === '_none') {
        continue;
      }
      elseif (!in_array($key, $organization_result_types)) {
        unset($form['result_types']['widget']['#options'][$key]);
      }
    }
    asort($form['result_types']['widget']['#options']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Event start date must be older or equal to end date.
    if (
      !empty($form_state->getValue('start_date')[0]['value']) &&
      !empty($form_state->getValue('end_date')[0]['value'])
    ) {
      $start_date = new DrupalDateTime($form_state->getValue('start_date')[0]['value'], new DateTimezone(DATETIME_STORAGE_TIMEZONE));
      $end_date = new DrupalDateTime($form_state->getValue('end_date')[0]['value'], new DateTimezone(DATETIME_STORAGE_TIMEZONE));
      if (
        !$start_date->hasErrors() &&
        !$end_date->hasErrors() &&
        $start_date->format('U') > $end_date->format('U')
      ) {
        $form_state->setErrorByName('end_date', $this->t('End date must be later than start date.'));
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
        drupal_set_message($this->t('Created the %label filter.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label filter.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect(
      'entity.filter.canonical', [
        'organization' => PathHelper::transliterate(Drupal::request()->get('organization')->label()),
        'filter' => $entity->id(),
      ]);
  }

}
