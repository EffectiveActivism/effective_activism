<?php

namespace Drupal\effective_activism\Plugin\Field\FieldWidget;

use DateTimeZone;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeWidgetBase;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;

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
class DateTimePickerWidget extends DateTimeWidgetBase implements ContainerFactoryPluginInterface {

  const DATETIMEPICKER_FORMAT = 'Y-m-d H:i';
  const DATETIMEPICKER_FORMAT_EXAMPLE = '2018-12-31 23:59';

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      if (!empty($item['value'])) {
        // Date value is now string not instance of DrupalDateTime (without T).
        $date = new DrupalDateTime($item['value'], new DateTimezone(DATETIME_STORAGE_TIMEZONE));
        $item['value'] = $date->hasErrors() ? '' : $date->format(DATETIME_DATETIME_STORAGE_FORMAT);
      }
    }
    return $values;
  }

  /**
   * The date format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityStorageInterface $date_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->dateStorage = $date_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity.manager')->getStorage('date_format')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    // Field type.
    $element['value'] = [
      '#title' => $element['#title'],
      '#type' => 'datetimepicker',
      '#date_timezone' => drupal_get_user_timezone(),
      '#default_value' => NULL,
      '#required' => $element['#required'],
      '#element_validate' => [
        [$this, 'validateDate'],
      ],
    ];
    if ($items[$delta]->date) {
      $date = $items[$delta]->date;
      $date->setTimezone(new DateTimeZone($element['value']['#date_timezone']));
      $element['value']['#default_value'] = $date->format(self::DATETIMEPICKER_FORMAT);
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateDate($element, FormStateInterface $form_state) {
    $date = new DrupalDateTime($element['#value'], new DateTimezone(DATETIME_STORAGE_TIMEZONE));
    if ($date->hasErrors()) {
      $form_state->setError($element, $this->t('The date format is wrong. Please submit a date in the following format: @format', [
        '@format' => self::DATETIMEPICKER_FORMAT_EXAMPLE,
      ]));
    }
  }

}
