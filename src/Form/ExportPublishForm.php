<?php

namespace Drupal\effective_activism\Form;

use Drupal;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\effective_activism\Entity\Export;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Helper\PathHelper;
use Drupal\effective_activism\Helper\Publish\Publisher;
use ReflectionClass;

/**
 * Form controller for Export publish forms.
 *
 * @ingroup effective_activism
 */
class ExportPublishForm extends ConfirmFormBase {

  const FORM_ID = 'publish_export';

  /**
   * Is published.
   *
   * @var bool
   */
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
  public function buildForm(array $form, FormStateInterface $form_state, Organization $organization = NULL, Group $group = NULL, Export $export = NULL) {
    // Export publish form can be viewed from two locations, organization level
    // and group level. Only one location is valid, so we check to make sure
    // that an invalid choice hasn't been made.
    if (
      (Drupal::request()->get('group') !== NULL && $export->parent->isEmpty()) ||
      (Drupal::request()->get('group') === NULL && !$export->parent->isEmpty())
      ) {
      drupal_set_message($this->t('Please view this page from the proper path.'), 'error');
      return $form;
    }
    $form = parent::buildForm($form, $form_state);
    $form['#theme'] = (new ReflectionClass($this))->getShortName();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $question = NULL;
    $entity = Drupal::request()->get('export');
    if (empty($entity)) {
      $question = $this->t('Export not found');
    }
    else {
      $this->isPublished = $entity->isPublished();
      if ($this->isPublished === TRUE) {
        $question = $this->t('Are you sure you want to unpublish the export?');
      }
      else {
        $question = $this->t('Are you sure you want to publish the export?');
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
      $description = $this->t('This action will unpublish the export and all its events.');
    }
    else {
      $description = $this->t('This action will publish the export and all its events.');
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
    $entity = Drupal::request()->get('export');
    if (Drupal::request()->get('group') === NULL) {
      return new Url(
        'entity.export.canonical', [
          'organization' => PathHelper::transliterate(Drupal::request()->get('organization')->label()),
          'export' => $entity->id(),
        ]
      );
    }
    else {
      return new Url(
        'entity.export.group_canonical', [
          'organization' => PathHelper::transliterate(Drupal::request()->get('organization')->label()),
          'group' => PathHelper::transliterate(Drupal::request()->get('group')->label()),
          'export' => $entity->id(),
        ]
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = Drupal::request()->get('export');
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
