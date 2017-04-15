<?php

namespace Drupal\effective_activism\Form\Event;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\effective_activism\Helper\PublishHelper;

/**
 * Form controller for Event publish forms.
 *
 * @ingroup effective_activism
 */
class EventPublishForm extends ConfirmFormBase {

  const FORM_ID = 'publish_event';

  private $isPublished;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return self::FORM_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $question = NULL;
    $entity = \Drupal::request()->get('event');
    if (empty($entity)) {
      $question = $this->t('Event not found');
    }
    else {
      $this->isPublished = $entity->isPublished();
      if ($this->isPublished === TRUE) {
        $question = empty($entity->get('title')->value) ? $this->t('Are you sure you want to unpublish the event') : $this->t('Are you sure you want to unpublish <em>@title</em>?', [
          '@title' => $entity->get('title')->value,
        ]);
      }
      else {
        $question = empty($entity->get('title')->value) ? $this->t('Are you sure you want to publish the event') : $this->t('Are you sure you want to publish <em>@title</em>?', [
          '@title' => $entity->get('title')->value,
        ]);
      }
    }
    return $question;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $description = NULL;
    if ($this->isPublished === TRUE) {
      $description = $this->t('This action will unpublish the event and its results.');
    }
    else {
      $description = $this->t('This action will publish the event and its results.');
    }
    return $description;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    $confirmation = NULL;
    if ($this->isPublished === TRUE) {
      $confirmation = $this->t('Unpublish');
    }
    else {
      $confirmation = $this->t('Publish');
    }
    return $confirmation;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $entity = \Drupal::request()->get('event');
    return new Url(
      'entity.event.canonical', [
        'event' => $entity->id(),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = \Drupal::request()->get('event');
    if ($this->isPublished === TRUE) {
      PublishHelper::unpublish($entity);
      empty($entity->get('title')->value) ? drupal_set_message(t('Event has been unpublished')) : drupal_set_message(t('<em>@title</em> has been unpublished', [
        '@title' => $entity->get('title')->value,
      ]));
    }
    else {
      PublishHelper::publish($entity);
      empty($entity->get('title')->value) ? drupal_set_message(t('Event has been published')) : drupal_set_message(t('<em>@title</em> has been published', [
        '@title' => $entity->get('title')->value,
      ]));
    }
    // Redirect the user to the list controller when complete.
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
