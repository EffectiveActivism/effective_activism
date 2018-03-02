<?php

namespace Drupal\effective_activism\Form;

use Drupal;
use Drupal\Core\Url;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Entity\EventTemplate;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Helper\EventTemplateHelper;
use Drupal\effective_activism\Helper\PathHelper;
use ReflectionClass;

/**
 * Form controller for event template delete forms.
 *
 * @ingroup effective_activism
 */
class EventTemplateDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Organization $organization = NULL, EventTemplate $event_template = NULL) {
    $form = parent::buildForm($form, $form_state);
    $form['#theme'] = (new ReflectionClass($this))->getShortName();
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (count(EventTemplateHelper::getEvents(Drupal::request()->get('event_template'), 0, 0, FALSE)) > 0) {
      $form_state->setErrorByName('submit', $this->t('This template is in use and cannot be deleted.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.event_template.canonical', [
      'organization' => PathHelper::transliterate(Drupal::request()->get('organization')->label()),
      'event_template' => PathHelper::transliterate(Drupal::request()->get('event_template')->id()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl() {
    return new Url('entity.organization.event_templates', [
      'organization' => PathHelper::transliterate(Drupal::request()->get('organization')->label()),
    ]);
  }

}
