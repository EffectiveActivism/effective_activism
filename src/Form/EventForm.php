<?php

namespace Drupal\effective_activism\Form;

use DateTimeZone;
use Drupal;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Entity\EventTemplate;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Entity\ResultType;
use Drupal\effective_activism\Helper\EventTemplateHelper;
use Drupal\effective_activism\Helper\PathHelper;
use Drupal\effective_activism\Entity\ThirdPartyContent;
use ReflectionClass;

/**
 * Form controller for Event edit forms.
 *
 * @ingroup effective_activism
 */
class EventForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Organization $organization = NULL, Group $group = NULL, EventTemplate $event_template = NULL) {
    /* @var $entity \Drupal\effective_activism\Entity */
    $form = parent::buildForm($form, $form_state);
    $form['#theme'] = (new ReflectionClass($this))->getShortName();
    $entity = $this->entity;
    // Set values from path.
    $form['parent']['widget'][0]['target_id']['#default_value'] = $group;
    $form['event_template']['widget'][0]['target_id']['#default_value'] = empty($event_template) ? NULL : $event_template;
    // Use event template if valid.
    if (
      $entity->isNew() &&
      $event_template !== NULL &&
      $event_template->access('view')
    ) {
      $form = EventTemplateHelper::applyEventTemplate($event_template, $form);
    }
    // Never allow adding existing results. However, we have to enable the
    // 'allow_existing' setting to force inline entity form to display
    // the 'Remove' button.
    unset($form['results']['widget']['actions']['ief_add_existing']);
    // Limit result inline entity form options by result type access settings.
    if (!empty($form['results']['widget']['actions']['bundle']['#options'])) {
      foreach ($form['results']['widget']['actions']['bundle']['#options'] as $machine_name => $human_name) {
        $result_type = ResultType::load($machine_name);
        if (!empty($result_type)) {
          if (
            !(in_array($group->id(), $result_type->get('groups')) ||
            (in_array(Constant::RESULT_TYPE_ALL_GROUPS, $result_type->get('groups')) &&
            (int) $organization->id() === $result_type->get('organization')))
          ) {
            unset($form['results']['widget']['actions']['bundle']['#options'][$machine_name]);
          }
        }
      }
      // If there are no options left, hide add button.
      if (empty($form['results']['widget']['actions']['bundle']['#options'])) {
        unset($form['results']['widget']['actions']['ief_add']);
        unset($form['results']['widget']['actions']['bundle']);
      }
    }
    // ...also check if there is only one result type to add.
    elseif (!empty($form['results']['widget']['actions']['bundle']['#value'])) {
      $result_type = ResultType::load($form['results']['widget']['actions']['bundle']['#value']);
      if (!empty($result_type)) {
        if (
          !(in_array($group->id(), $result_type->get('groups')) ||
          (in_array(Constant::RESULT_TYPE_ALL_GROUPS, $result_type->get('groups')) &&
          (int) $organization->id() === $result_type->get('organization')))
        ) {
          unset($form['results']['widget']['actions']['ief_add']);
        }
      }
    }
    // Manually set placeholder on link element, as it doesn't support it via
    // field settings.
    $form['link']['widget'][0]['uri']['#placeholder'] = t('Link to event on other websites');
    // Attach scroll function.
    $form['#attached']['library'][] = 'effective_activism/scroll';
    // Hide fields.
    $form['user_id']['#attributes']['class'][] = 'hidden';
    $form['revision_log_message']['#attributes']['class'][] = 'hidden';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Only allow user to change group if existing results are allowed
    // in new group.
    if (!empty($form_state->getValue('results')['entities'])) {
      // Iterate all inline entity forms to find results.
      foreach ($form_state->get('inline_entity_form') as &$widget_state) {
        if (!empty($widget_state['instance'])) {
          if ($widget_state['instance']->getName() === 'results') {
            foreach ($widget_state['entities'] as $delta => $entity_item) {
              if (!empty($entity_item['entity'])) {
                $result = $entity_item['entity'];
                $result_type = ResultType::load($result->getType());
                if (!empty($result_type)) {
                  $allowed_gids = $result_type->get('groups');
                  if (
                    !in_array(Drupal::request()->get('group')->id(), $allowed_gids) &&
                    !in_array(Constant::RESULT_TYPE_ALL_GROUPS, $allowed_gids)
                  ) {
                    $form_state->setErrorByName('parent', $this->t('<em>@group</em> does not allow the result type <em>@result_type</em>. Please select another group or remove the result.', [
                      '@group' => Drupal::request()->get('group')->label(),
                      '@result_type' => $result_type->get('label'),
                    ]));
                    break 2;
                  }
                }
              }
            }
          }
        }
      }
    }
    // Event start date must be older or equal to end date.
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

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    if (!$entity->isNew()) {
      // If event address or times are changed, we need to update the
      // third-party content.
      $original_entity = Drupal::entityTypeManager()->getStorage('event')->loadUnchanged($entity->id());
      $start_date_value = $entity->get('start_date')->getValue();
      $start_date = new DrupalDateTime($start_date_value[0]['value'], new DateTimezone(DATETIME_STORAGE_TIMEZONE));
      $original_start_date_value = $original_entity->get('start_date')->getValue();
      $original_start_date = new DrupalDateTime($original_start_date_value[0]['value'], new DateTimezone(DATETIME_STORAGE_TIMEZONE));
      $address = isset($entity->location->address) ? $entity->get('location')->address : NULL;
      $original_address = isset($entity->original->location->address) ? $original_entity->get('location')->address : NULL;
      if (
        ($start_date->getTimestamp() !== $original_start_date->getTimestamp() ||
          $address !== $original_address) &&
        !$entity->third_party_content->isEmpty()
      ) {
        // If other events are using the third-party content, remove it from
        // this event. The AddThirdPartyContent cron job will pick it up.
        $third_party_content_entities = [];
        foreach ($entity->third_party_content->getValue() as $delta => $value) {
          $query = Drupal::entityQuery('event');
          $number_of_events = $query
            ->condition('third_party_content', $value['target_id'])
            ->count()
            ->execute();
          // Only keep third-party content that isn't used by other events.
          if ((int) $number_of_events === 1) {
            $third_party_content_entities[] = ['target_id' => $value['target_id']];
            $third_party_content = ThirdPartyContent::load($value['target_id']);
            $third_party_content->setPublished(FALSE);
            // Re-add location and time information, as applicable.
            $third_party_content->set('field_latitude', $entity->get('location')->latitude);
            $third_party_content->set('field_longitude', $entity->get('location')->longitude);
            if (in_array($third_party_content->bundle(), Constant::THIRD_PARTY_CONTENT_TIME_AWARE)) {
              $third_party_content->set('field_timestamp', $entity->get('start_date')->date->format('U'));
            }
            $third_party_content->setNewRevision(TRUE);
            $third_party_content->save();
          }
        }
        // If the number of third_party_content references has changed, re-add.
        if (count($entity->third_party_content->getValue()) !== count($third_party_content_entities)) {
          $entity->get('third_party_content')->setValue($third_party_content_entities);
        }
      }
    }
    $entity->setNewRevision();
    $status = parent::save($form, $form_state);
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created event.'));
        break;

      default:
        drupal_set_message($this->t('Saved the event.'));
    }
    $form_state->setRedirect('entity.event.canonical', [
      'organization' => PathHelper::transliterate(Drupal::request()->get('organization')->label()),
      'group' => PathHelper::transliterate(Drupal::request()->get('group')->label()),
      'event' => $entity->id(),
    ]);
  }

  /**
   * Returns the form array when using AJAX.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form array.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

}
