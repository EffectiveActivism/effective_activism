<?php

namespace Drupal\effective_activism\Form;

use Drupal;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Entity\ResultType;
use Drupal\effective_activism\Helper\PathHelper;
use Drupal\effective_activism\Helper\ResultTypeHelper;
use ReflectionClass;

/**
 * Form controller for result type delete forms.
 *
 * @ingroup effective_activism
 */
class ResultTypeDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Organization $organization = NULL, ResultType $result_type = NULL) {
    $form = parent::buildForm($form, $form_state);
    $form['#theme'] = (new ReflectionClass($this))->getShortName();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.organization.result_types', [
      'organization' => PathHelper::transliterate(Drupal::request()->get('organization')->label()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', [
      '%name' => $this->entity->get('importname'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl() {
    return new Url('entity.organization.result_types', [
      'organization' => PathHelper::transliterate(Drupal::request()->get('organization')->label()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    drupal_set_message($this->t('Deleted result type @result_type.', [
      '@result_type' => $this->entity->label(),
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $event_count = count(ResultTypeHelper::getEvents(Drupal::request()->get('result_type'), 0, 0, FALSE));
    if ($event_count > 0) {
      $form_state->setErrorByName('submit', $this->formatPlural(
        $event_count,
        'This result type is used by one event and cannot be deleted.',
        'This result type is used by @count events and cannot be deleted.'
      ));
    }
  }

}
