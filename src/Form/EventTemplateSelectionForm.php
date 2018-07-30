<?php

namespace Drupal\effective_activism\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Helper\OrganizationHelper;
use Drupal\effective_activism\Helper\PathHelper;
use ReflectionClass;

/**
 * Form controller for selecting an event template.
 *
 * @ingroup effective_activism
 */
class EventTemplateSelectionForm extends FormBase {

  const FORM_ID = 'effective_activism_event_template_selection';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return self::FORM_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Organization $organization = NULL, Group $group = NULL) {
    $form['#theme'] = (new ReflectionClass($this))->getShortName();
    // Get available event templates.
    $event_templates = array_reduce(OrganizationHelper::getEventTemplates($organization), function ($result, $event_template) {
      if ($event_template->isPublished()) {
        $result[$event_template->id()] = $event_template->label();
      }
      return $result;
    });
    asort($event_templates);
    if (!empty($event_templates)) {
      $form['event_template'] = [
        '#type' => 'select',
        '#title' => $this->t('Event template'),
        '#default_value' => empty($available_event_templates) ? NULL : key($available_event_templates),
        '#description' => $this->t('The event template to use.'),
        '#options' => $event_templates,
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
        '#type' => 'item',
        '#title' => $this->t('Event template'),
        '#markup' => sprintf('<p>%s</p>', $this->t('There are no available event templates.')),
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.event.add_from_template_form', [
      'organization' => PathHelper::transliterate(Drupal::request()->get('organization')->label()),
      'group' => PathHelper::transliterate(Drupal::request()->get('group')->label()),
      'event_template' => $form_state->getValue('event_template'),
    ]);
  }

}
