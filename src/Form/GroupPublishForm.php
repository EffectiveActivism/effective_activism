<?php

namespace Drupal\effective_activism\Form;

use Drupal;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Helper\PathHelper;
use Drupal\effective_activism\Helper\Publish\Publisher;
use ReflectionClass;

/**
 * Form controller for Group publish forms.
 *
 * @ingroup effective_activism
 */
class GroupPublishForm extends ConfirmFormBase {

  const FORM_ID = 'publish_group';

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
  public function buildForm(array $form, FormStateInterface $form_state, Organization $organization = NULL, Group $group = NULL) {
    $form = parent::buildForm($form, $form_state);
    $form['#theme'] = (new ReflectionClass($this))->getShortName();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $question = NULL;
    $entity = Drupal::request()->get('group');
    if (empty($entity)) {
      $question = $this->t('Group not found');
    }
    else {
      $this->isPublished = $entity->isPublished();
      if ($this->isPublished === TRUE) {
        $question = $this->t('Are you sure you want to unpublish <em>@title</em>?', [
          '@title' => $entity->label(),
        ]);
      }
      else {
        $question = $this->t('Are you sure you want to publish <em>@title</em>?', [
          '@title' => $entity->label(),
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
      $description = $this->t('This action will unpublish the group and all its events, imports and results.');
    }
    else {
      $description = $this->t('This action will publish the group and all its events, imports and results.');
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
    $entity = Drupal::request()->get('group');
    return new Url(
      'entity.group.canonical', [
        'organization' => PathHelper::transliterate(Drupal::request()->get('organization')->label()),
        'group' => PathHelper::transliterate(Drupal::request()->get('group')->label()),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = Drupal::request()->get('group');
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
