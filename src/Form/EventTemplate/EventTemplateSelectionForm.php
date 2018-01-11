<?php

namespace Drupal\effective_activism\Form\EventTemplate;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Entity\EventTemplate;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Helper\OrganizationHelper;
use Drupal\effective_activism\Helper\AccountHelper;

/**
 * Form controller for selecting an event template.
 *
 * @ingroup effective_activism
 */
class EventTemplateSelectionForm extends FormBase {

  const FORM_ID = 'effective_activism_event_template_selection';

  const AJAX_WRAPPER = 'ajax-event-template-selection';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return self::FORM_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get available organizations.
    $available_organizations = array_reduce(AccountHelper::getManagedOrganizations(), function ($result, $organization) {
      $result[$organization->id()] = $organization->label();
      return $result;
    }, []);
    // Get organization default value.
    $selected_organization = !empty($form_state->getValue('organization')) ? $form_state->getValue('organization') : key($available_organizations);
    // Get available event templates.
    $available_event_templates = array_reduce(OrganizationHelper::getEventTemplates(Organization::load($selected_organization)), function ($result, $event_template) {
      $result[$event_template->id()] = $event_template->label();
      return $result;
    });
    // Get event template default value.
    $selected_event_template = NULL;
    if (!empty($form_state->getValue('event_template')) && in_array($form_state->getValue('event_template'), $available_event_templates)) {
      $selected_event_template = $form_state->getValue('event_template');
    }
    elseif (!empty($available_event_templates)) {
     $selected_event_template = key($available_event_templates);
    }
    $form['#theme'] = sprintf('%s-form', self::FORM_ID);
    $form['organization'] = [
      '#type' => 'select',
      '#title' => $this->t('Organization'),
      '#default_value' => $selected_organization,
      '#tags' => TRUE,
      '#description' => $this->t('The organization that the event template belongs to.'),
      '#options' => $available_organizations,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'updateAvailableEventTemplates'],
        'wrapper' => self::AJAX_WRAPPER,
      ],
    ];
    if (!empty($available_event_templates)) {
      $form['event_template'] = [
        '#prefix' => sprintf('<div id="%s">', self::AJAX_WRAPPER),
        '#suffix' => '</div>',
        '#type' => 'select',
        '#title' => $this->t('Event template'),
        '#default_value' => $selected_event_template,
        '#description' => $this->t('The event template to use.'),
        '#options' => $available_event_templates,
        '#required' => TRUE,
      ];
      $form['select'] = [
        '#type' => 'submit',
        '#value' => $this->t('Select'),
        '#name' => 'select',
      ];
    }
    else {
      $form['event_template'] = [
        '#prefix' => sprintf('<div id="%s">', self::AJAX_WRAPPER),
        '#suffix' => '</div>',
        '#type' => 'item',
        '#title' => $this->t('Event template'),
        '#markup' => sprintf('<p>%s</p>', $this->t('There are no available event templates for this organization.')),
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('activeforanimals.event.from-template', [
      'event_template' => $form_state->getValue('event_template'),
    ]);
  }

  /**
   * Populates the event template #options element.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form array.
   */
  public function updateAvailableEventTemplates(array &$form, FormStateInterface $form_state) {
    return $form['event_template'];
  }

}
