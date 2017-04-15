<?php

namespace Drupal\effective_activism\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Helper\LocationHelper;

/**
 * Plugin implementation of the location widget.
 *
 * @FieldWidget(
 *   id = "location_default",
 *   label = @Translation("Location widget"),
 *   field_types = {
 *     "location"
 *   }
 * )
 */
class LocationWidget extends WidgetBase implements WidgetInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $address = isset($items[$delta]->address) ? $items[$delta]->address : NULL;
    $extra_information = isset($items[$delta]->extra_information) ? $items[$delta]->extra_information : NULL;
    $element['address'] = [
      '#title' => $this->t('Address'),
      '#type' => 'textfield',
      '#default_value' => $address,
      '#autocomplete_route_name' => 'effective_activism.location.autocomplete',
      '#autocomplete_route_parameters' => [],
      '#size' => 30,
      '#maxlength' => 255,
      '#element_validate' => [
        [$this, 'validateAddress'],
      ],
      '#attached' => [
        'library' => ['effective_activism/autocomplete'],
      ],
      '#placeholder' => $this->t('Address'),
      '#title_display' => 'hidden',
      '#attributes' => [
        'class' => ['address'],
      ],
    ];
    $element['extra_information'] = [
      '#title' => $this->t('Other location information'),
      '#type' => 'textfield',
      '#default_value' => $extra_information,
      '#size' => 30,
      '#maxlength' => 255,
      '#placeholder' => $this->t('Other address info'),
      '#title_display' => 'hidden',
      '#attributes' => [
        'class' => ['extra-information'],
      ],
    ];
    return $element;
  }

  /**
   * Validate the address.
   */
  public function validateAddress($element, FormStateInterface $form_state) {
    $address = $element['#value'];
    if (!empty($address)) {
      if (!LocationHelper::validateAddress($address)) {
        $form_state->setError($element, t('Please select an address from the list of suggestions.'));
      }
    }
  }

}
