<?php

namespace Drupal\effective_activism\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\ResultType;
use Drupal\effective_activism\Helper\EventTemplateHelper;
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
  public function buildForm(array $form, FormStateInterface $form_state, $event_template = NULL) {
    /* @var $entity \Drupal\effective_activism\Entity */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;
    // Set form theme id.
    $form['#theme'] = (new ReflectionClass($this))->getShortName();
    // Retrieve group id.
    $gid = $form_state->getTemporaryValue('gid');
    // If the form is fresh, it has no parent group id.
    // Use default value instead.
    if (empty($gid) && !empty($form['parent']['widget'][0]['target_id']['#default_value'])) {
      $gid = $form['parent']['widget'][0]['target_id']['#default_value'];
    }
    // Use event template if valid.
    if (
      $entity->isNew() &&
      $event_template !== NULL &&
      $event_template->access('view')
    ) {
      $form = EventTemplateHelper::applyEventTemplate($event_template, $form);
      $form_state->setTemporaryValue('event_template_id', $event_template->id());
    }
    $form['#prefix'] = '<div id="ajax">';
    $form['#suffix'] = '</div>';
    $form['parent']['widget'][0]['target_id']['#ajax'] = [
      'callback' => [$this, 'ajaxCallback'],
      'wrapper' => 'ajax',
    ];
    // Never allow adding existing results. However, we have to enable the
    // 'allow_existing' setting to force inline entity form to display
    // the 'Remove' button.
    unset($form['results']['widget']['actions']['ief_add_existing']);
    // Limit result inline entity form options by result type access settings.
    if (!empty($form['results']['widget']['actions']['bundle']['#options'])) {
      foreach ($form['results']['widget']['actions']['bundle']['#options'] as $machine_name => $human_name) {
        $result_type = ResultType::load($machine_name);
        if (!empty($result_type)) {
          if (!in_array($gid, $result_type->get('groups'))) {
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
        if (!in_array($gid, $result_type->get('groups'))) {
          unset($form['results']['widget']['actions']['ief_add']);
        }
      }
    }
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
    // Make the selected group id persistent across form states.
    // We cannot rely on the form_state values, because inline entity forms
    // only submit a subset of values.
    $gid = $form_state->getValue('parent')[0]['target_id'];
    $form_state->setTemporaryValue('gid', $gid);
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
                  if (!in_array($gid, $allowed_gids)) {
                    $form_state->setErrorByName('parent', $this->t('<em>@group</em> does not allow the result type <em>@result_type</em>. Please select another group or remove the result.', [
                      '@group' => Group::load($gid)->label(),
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
    $start_date = $form_state->getValue('start_date')[0]['value'];
    $end_date = $form_state->getValue('end_date')[0]['value'];
    if (
      $start_date instanceof DrupalDateTime &&
      $end_date instanceof DrupalDateTime &&
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
    $entity->setNewRevision();
    // Store event template if any is used.
    $event_template_id = $form_state->getTemporaryValue('event_template_id');
    if (!empty($event_template_id)) {
      $entity->event_template->target_id = $event_template_id;
    }
    $status = parent::save($form, $form_state);
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created event.'));
        break;

      default:
        drupal_set_message($this->t('Saved the event.'));
    }
    $form_state->setRedirect('entity.event.canonical', ['event' => $entity->id()]);
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
