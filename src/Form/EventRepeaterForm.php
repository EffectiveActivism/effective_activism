<?php

namespace Drupal\effective_activism\Form;

use DateInterval;
use DatePeriod;
use DateTime;
use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Helper\DateHelper;
use Drupal\effective_activism\Helper\PathHelper;
use ReflectionClass;

/**
 * Form controller for repeating events.
 *
 * @ingroup effective_activism
 */
class EventRepeaterForm extends FormBase {

  const FORM_ID = 'effective_activism_event_repeater';

  const MIN_STEPS = 1;
  const MAX_STEPS = 99;
  const MIN_REPEATS = 1;
  const MAX_REPEATS = 10;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return self::FORM_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Organization $organization = NULL, Group $group = NULL, Event $event = NULL) {
    // Do not allow repeating of old events.
    $now = DateHelper::getNow($organization, $group);
    $start_date = new DateTime($event->start_date->value);
    if ($start_date->format('U') < $now->format('U')) {
      drupal_set_message(t('You cannot repeat old events'), 'error');
      return $form;
    }
    $form['#theme'] = (new ReflectionClass($this))->getShortName();
    $form['step'] = [
      '#type' => 'number',
      '#title' => $this->t('Step'),
      '#default_value' => self::MIN_STEPS,
      '#description' => $this->t('The step or interval between repeats.'),
      '#min' => self::MIN_STEPS,
      '#max' => self::MAX_STEPS,
      '#required' => TRUE,
    ];
    $form['frequency'] = [
      '#type' => 'select',
      '#title' => $this->t('Frequency'),
      '#default_value' => 'D',
      '#description' => $this->t('Which frequency to repeat by.'),
      '#options' => [
        'D' => 'Day',
        'W' => 'Week',
        'M' => 'Month',
        'Y' => 'Year',
      ],
      '#required' => TRUE,
    ];
    $form['repeats'] = [
      '#type' => 'number',
      '#title' => $this->t('Repeats'),
      '#default_value' => self::MIN_REPEATS,
      '#description' => $this->t('Number of times to repeat the event.'),
      '#min' => self::MIN_REPEATS,
      '#max' => self::MAX_REPEATS,
      '#required' => TRUE,
    ];
    $form['select'] = [
      '#type' => 'submit',
      '#value' => $this->t('Repeat'),
      '#name' => 'select',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $event = Drupal::request()->get('event');
    // Do not allow repeating of old events.
    $now = DateHelper::getNow(Drupal::request()->get('organization'), Drupal::request()->get('group'));
    $start_date = new DateTime($event->start_date->value);
    if ($start_date->format('U') < $now->format('U')) {
      $form_state->setRedirect('entity.group.events', [
        'organization' => PathHelper::transliterate(Drupal::request()->get('organization')->label()),
        'group' => PathHelper::transliterate(Drupal::request()->get('group')->label()),
      ]);
      drupal_set_message(t('You cannot repeat old events'), 'error');
      return;
    }
    // Create periods from start date, step and frequency.
    $start_date = new DateTime($event->start_date->value);
    $difference = $start_date->diff(new DateTime($event->end_date->value));
    $interval = new DateInterval(sprintf('P%d%s', $form_state->getValue('step'), $form_state->getValue('frequency')));
    $date_period = new DatePeriod($start_date, $interval, $form_state->getValue('repeats'));
    // Map periods onto existing events and create new events until
    // MAX_REPEATS is reached.
    $i = 1;
    foreach ($date_period as $new_start_date) {
      $new_end_date = clone $new_start_date;
      $new_end_date->add($difference);
      if ($i > self::MAX_REPEATS) {
        break;
      }
      $repeated_event = $event->createDuplicate();
      $repeated_event->start_date->setValue($new_start_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));
      $repeated_event->end_date->setValue($new_end_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));
      // Do not include results.
      $repeated_event->results->setValue(NULL);
      $repeated_event->save();
    }
    drupal_set_message(Drupal::translation()->formatPlural(
      $form_state->getValue('repeats'),
      'Reapeted event once.',
      'Repeated event @repeats times.', [
        '@repeats' => $form_state->getValue('repeats'),
      ]
    ));
    $form_state->setRedirect('entity.group.events', [
      'organization' => PathHelper::transliterate(Drupal::request()->get('organization')->label()),
      'group' => PathHelper::transliterate(Drupal::request()->get('group')->label()),
    ]);
  }

}
