<?php

namespace Drupal\effective_activism\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\WidgetInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeWidgetBase;

/**
 * Plugin implementation of the DateTimePickerWidget widget.
 *
 * @FieldWidget(
 *   id = "datetimepicker_widget",
 *   label = @Translation("Date Time Picker"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class DateTimePickerWidget extends DateTimeWidgetBase implements WidgetInterface {

  const DATETIMEPICKER_FORMAT = 'Y-m-d H:i';
  const DATETIMEPICKER_FORMAT_EXAMPLE = '2018-12-31 23:59';

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      if (!empty($item['value'])) {
        // Date value is now string not instance of DrupalDateTime (without T).
        $date = new DrupalDateTime($item['value']);
        $item['value'] = $date->hasErrors() ? '' : $date->format(DATETIME_DATETIME_STORAGE_FORMAT);
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    // Field type.
    $element['value'] = [
      '#title' => $element['#title'],
      '#type' => 'textfield',
      '#date_timezone' => drupal_get_user_timezone(),
      '#default_value' => NULL,
      '#required' => $element['#required'],
      '#element_validate' => [
        [$this, 'validateDate'],
      ],
      '#attached' => [
        'library' => ['effective_activism/datetimepicker'],
      ],
      '#attributes' => [
        'class' => ['datetimepicker'],
      ],
    ];
    if ($items[$delta]->date) {
      $date = $items[$delta]->date;
      $element['value']['#default_value'] = $date->format(self::DATETIMEPICKER_FORMAT);
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateDate($element, FormStateInterface $form_state) {
    $date = new DrupalDateTime($element['#value']);
    if ($date->hasErrors()) {
      $form_state->setError($element, $this->t('The date format is wrong. Please submit a date in the following format: @format', [
        '@format' => self::DATETIMEPICKER_FORMAT_EXAMPLE,
      ]));
    }
  }

}
