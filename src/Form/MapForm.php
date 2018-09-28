<?php

namespace Drupal\effective_activism\Form;

use Drupal;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Entity\Filter;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Helper\MapHelper;
use Drupal\effective_activism\Helper\OrganizationHelper;
use ReflectionClass;

/**
 * Form controller for Map forms.
 *
 * @ingroup effective_activism
 */
class MapForm extends FormBase {

  const FORM_ID = 'effective_activism_map';

  const AJAX_WRAPPER = 'ajax-map';

  const MAP_TYPE_OPTIONS = [
    'map' => 'Map',
    'heatmap' => 'Heatmap',
  ];

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
    // Carry any group on to submit handler.
    $form_state->setTemporaryValue('group', $group);
    // Get available filters.
    $available_filters = [];
    foreach (OrganizationHelper::getFilters($organization) as $filter_id => $filter) {
      $available_filters[$filter_id] = $filter->getName();
    }
    // Get filter default value.
    $selected_filter = $form_state->getValue('filter', key($available_filters));
    // Get available data types.
    $data_bundles = Drupal::entityManager()->getBundleInfo('data');
    $available_datatypes = [
      '_none' => $this->t('None'),
    ];
    foreach ($data_bundles as $bundle_name => $bundle_info) {
      $available_datatypes[$bundle_name] = $bundle_info['label'];
    }
    $form['#theme'] = (new ReflectionClass($this))->getShortName();
    $form['filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter'),
      '#default_value' => $selected_filter,
      '#description' => $this->t('The filter to show a chart for.'),
      '#options' => $available_filters,
      '#required' => TRUE,
    ];
    $form['map_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#default_value' => 'map',
      '#description' => $this->t('The type of map to display.'),
      '#options' => self::MAP_TYPE_OPTIONS,
      '#required' => TRUE,
    ];
    $form['data_type'] = [
      '#default_value' => '_none',
      '#title' => $this->t('Data type'),
      '#type' => 'select',
      '#options' => $available_datatypes,
    ];
    $form['map'] = [
      '#type' => 'markup',
      '#children' => '<div id="leaflet-map" style="height: 40em;"></div>',
      '#prefix' => sprintf('<div id="%s">', self::AJAX_WRAPPER),
      '#suffix' => '</div>',
      '#attached' => [
        'library' => [
          'effective_activism/leaflet',
          'effective_activism/leaflet-heatmap',
          'effective_activism/leaflet-markercluster',
        ],
        'drupalSettings' => [
          'leaflet' => [
            'key' => Drupal::config('effective_activism.settings')->get('mapbox_api_key'),
            'places' => MapHelper::getEventPlaces(Filter::load($selected_filter), $group, $form_state->getValue('data_type')),
            'type' => $form_state->getValue('map_type'),
          ],
        ],
      ],
    ];
    $form['submit'] = [
      '#type' => 'button',
      '#name' => 'submit_button',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => [$this, 'ajaxCallback'],
        'wrapper' => self::AJAX_WRAPPER,
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Ajax callback.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    // Clear any previous highcharts settings.
    $response->addCommand(new SettingsCommand([
      'leaflet' => NULL,
    ], TRUE));
    $places = MapHelper::getEventPlaces(Filter::load($form_state->getValue('filter')), $form_state->getTemporaryValue('group'), $form_state->getValue('data_type'));
    if (!empty($places)) {
      $response->addCommand(new SettingsCommand([
        'leaflet' => [
          'key' => Drupal::config('effective_activism.settings')->get('mapbox_api_key'),
          'places' => $places,
          'type' => $form_state->getValue('map_type'),
        ],
      ], TRUE));
      $response->addCommand(new InsertCommand(NULL, $form['map']));
    }
    return $response;
  }


}
