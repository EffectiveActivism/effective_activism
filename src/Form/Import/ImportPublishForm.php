<?php

namespace Drupal\effective_activism\Form\Import;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\effective_activism\Helper\Publish\Publisher;

/**
 * Form controller for Import publish forms.
 *
 * @ingroup effective_activism
 */
class ImportPublishForm extends ConfirmFormBase {

  const FORM_ID = 'publish_import';

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
    $entity = \Drupal::request()->get('import');
    if (empty($entity)) {
      $question = $this->t('Import not found');
    }
    else {
      $this->isPublished = $entity->isPublished();
      if ($this->isPublished === TRUE) {
        $question = $this->t('Are you sure you want to unpublish the import?');
      }
      else {
        $question = $this->t('Are you sure you want to publish the import?');
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
      $description = $this->t('This action will unpublish the import and all its events.');
    }
    else {
      $description = $this->t('This action will publish the import and all its events.');
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
    $entity = \Drupal::request()->get('import');
    return new Url(
      'entity.import.canonical', [
        'import' => $entity->id(),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $import = NULL) {
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = \Drupal::request()->get('import');
    if ($this->isPublished === TRUE) {
      $publisher = new Publisher($entity);
      $batch = [
        'title' => t('Unpublishing...'),
        'operations' => [[
          'Drupal\effective_activism\Helper\Publish\BatchProcess::unpublish',
          [$publisher],
        ],
        ],
        'finished' => 'Drupal\effective_activism\Helper\Publish\BatchProcess::unpublished',
      ];
      batch_set($batch);
    }
    else {
      $publisher = new Publisher($entity);
      $batch = [
        'title' => t('Publishing...'),
        'operations' => [[
          'Drupal\effective_activism\Helper\Publish\BatchProcess::publish',
          [$publisher],
        ],
        ],
        'finished' => 'Drupal\effective_activism\Helper\Publish\BatchProcess::published',
      ];
      batch_set($batch);
    }
    // Redirect the user to the list controller when complete.
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
