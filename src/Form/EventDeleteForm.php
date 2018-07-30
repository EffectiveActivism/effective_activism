<?php

namespace Drupal\effective_activism\Form;

use Drupal;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Helper\PathHelper;
use ReflectionClass;

/**
 * Form controller for Event delete forms.
 *
 * @ingroup effective_activism
 */
class EventDeleteForm extends ContentEntityConfirmFormBase {

  const FORM_ID = 'delete_event';

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
    $entity = Drupal::request()->get('event');
    if (empty($entity)) {
      $question = $this->t('Event not found');
    }
    else {
      $question = $entity->get('title')->isEmpty() ? $this->t('Are you sure you want to delete the event') : $this->t('Are you sure you want to delete <em>@title</em>?', [
        '@title' => $entity->get('title')->value,
      ]);
    }
    return $question;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action will permanently delete the event and its results.');
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
  public function getCancelUrl() {
    return new Url(
      'entity.event.canonical', [
        'organization' => PathHelper::transliterate(Drupal::request()->get('organization')->label()),
        'group' => PathHelper::transliterate(Drupal::request()->get('group')->label()),
        'event' => Drupal::request()->get('event')->id(),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $event = Drupal::request()->get('event');
    // Delete event results and data.
    foreach ($event->results->referencedEntities() as $delta => $result) {
      foreach ($result->getFields() as $field_name => $data) {
        // Delete any data entities.
        if (substr($field_name, 0, 5) === 'data_') {
          if (!$result->get($field_name)->isEmpty()) {
            try {
              $result->get($field_name)->entity->delete();
            }
            catch (EntityStorageException $exception) {
              Drupal::logger('effective_activism')->warning(sprintf('Failed to delete data with id %d when deleting event with id %d', $result->get($field_name)->entity->id(), $event->id()));
            }
          }
        }
      }
      try {
        $result->delete();
      }
      catch (EntityStorageException $exception) {
        Drupal::logger('effective_activism')->warning(sprintf('Failed to delete result with id %d when deleting event with id %d', $result->id(), $event->id()));
      }
    }
    // Delete third-party content.
    foreach ($event->third_party_content->referencedEntities() as $delta => $third_party_content) {
      $query = Drupal::entityQuery('event')
        ->condition('third_party_content', $third_party_content->id())
        ->sort('start_date')
        ->count();
      // Only delete third-party content that isn't referenced by other events.
      $count = (int) $query->execute();
      if ($count === 1) {
        $third_party_content->delete();
      }
    }
    try {
      $event->delete();
    }
    catch (EntityStorageException $exception) {
      Drupal::logger('effective_activism')->warning(sprintf('Failed to delete event with id %d', $event->id()));
    }

    drupal_set_message($this->t('Deleted event and results.'));
    Drupal::logger('effective_activism')->notice(sprintf('Deleted event with id %d', $event->id()));

    // Redirect the user to the list controller when complete.
    $form_state->setRedirectUrl(new Url(
      'entity.group.events', [
        'organization' => PathHelper::transliterate(Drupal::request()->get('organization')->label()),
        'group' => PathHelper::transliterate(Drupal::request()->get('group')->label()),
      ]
    ));
  }

}
