<?php

namespace Drupal\effective_activism\Form\Chart;

use DateInterval;
use DatePeriod;
use DateTime;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Chart\Providers\HighCharts\HighChartsChart;
use Drupal\effective_activism\Chart\Providers\HighCharts\HighChartsAxis;
use Drupal\effective_activism\Entity\Filter;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Helper\FilterHelper;
use Drupal\effective_activism\Helper\OrganizationHelper;

/**
 * Form controller for Chart forms.
 *
 * @ingroup effective_activism
 */
class ChartForm extends FormBase {

  const FORM_ID = 'effective_activism_chart';

  const THEME_ID = self::FORM_ID . '-form';

  const AJAX_WRAPPER = 'ajax-chart';

  const TIME_OPTIONS = [
    'daily' => [
      'label' => 'Daily',
      'slice' => 'Y - n/j',
      'interval' => 'P1D',
      'modifier' => '+1 day',
    ],
    'monthly' => [
      'label' => 'Monthly',
      'slice' => 'Y - M',
      'interval' => 'P1M',
      'modifier' => '+1 month',
    ],
  ];

  const DATA_FIELDS_BLACKLIST = [
    'field_currency',
  ];

  const DRUPAL_DATE_FORMAT = 'Y-m-d\TH:i:s';

  const SORT_CRITERIA = 'start_date';

  const CHART_TYPE_OPTIONS = [
    'line' => 'Line',
    'column' => 'Column',
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
  public function buildForm(array $form, FormStateInterface $form_state, Organization $organization = NULL) {
    // Get available filters.
    $available_filters = [];
    foreach (OrganizationHelper::getFilters($organization) as $filter_id => $filter) {
      $available_filters[$filter_id] = $filter->getName();
    }
    // Get filter default value.
    $selected_filter = !empty($form_state->getValue('filter')) ? Filter::load($form_state->getValue('filter')) : NULL;
    $form['#theme'] = self::THEME_ID;
    $form['filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter'),
      '#default_value' => isset($selected_filer) ? $selected_filter->id() : key($available_filters),
      '#description' => $this->t('The filter to show a chart for.'),
      '#options' => $available_filters,
      '#required' => TRUE,
    ];
    $form['series_1_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#default_value' => 'line',
      '#description' => $this->t('The chart type.'),
      '#options' => self::CHART_TYPE_OPTIONS,
      '#required' => TRUE,
    ];
    $form['series_1_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Interval'),
      '#default_value' => 'line',
      '#description' => $this->t('The time interval.'),
      '#options' => array_combine(array_keys(self::TIME_OPTIONS), array_map(function ($element) {
        return $element['label'];
      }, self::TIME_OPTIONS)),
      '#required' => TRUE,
    ];
    $form['chart'] = [
      '#prefix' => sprintf('<div id="%s">', self::AJAX_WRAPPER),
      '#suffix' => '</div>',
      '#type' => 'markup',
      '#markup' => '<div id="chart" style="min-width: 310px; height: 400px; margin: 0 auto"></div>',
      '#title' => $this->t('Event template'),
      '#description' => $this->t('The event template to use.'),
      '#attached' => [
        'library' => [
          'effective_activism/highcharts',
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
      'highcharts' => NULL,
    ], TRUE));
    $filter = Filter::load($form_state->getValue('filter'));
    // Get events.
    $events = FilterHelper::getEvents($filter);
    // Get oldest event.
    $oldest_event = reset($events);
    // Get newest event.
    $newest_event = end($events);
    if (empty($oldest_event) || empty($newest_event) || $oldest_event === $newest_event) {
      drupal_set_message('Time range is too small for this filter to display a graph. Try extending the filter date range or create more events.', 'warning');
      drupal_set_message($action);
      $form['messages']['status'] = [
        '#type' => 'status_messages',
      ];
      $response->addCommand(new InsertCommand(null, $form['messages']));

      return $response;
    }
    // Create timesliced array.
    // Add extra time to end date to ensure it is added.
    $period = new DatePeriod(
      DateTime::createFromFormat(self::DRUPAL_DATE_FORMAT, $oldest_event->get(self::SORT_CRITERIA)->value),
      new DateInterval(self::TIME_OPTIONS[$form_state->getValue('series_1_interval')]['interval']),
      DateTime::createFromFormat(self::DRUPAL_DATE_FORMAT, $newest_event->get(self::SORT_CRITERIA)->value)->modify(self::TIME_OPTIONS[$form_state->getValue('series_1_interval')]['modifier'])
    );
    $categories = [];
    foreach ($period as $date) {
      $categories[] = $date->format(self::TIME_OPTIONS[$form_state->getValue('series_1_interval')]['slice']);
    }
    // Populate categories and data.
    $series_value_label = '';
    $series_data = $participants = [];
    foreach ($events as $event) {
      foreach ($event->get('results') as $result_entity) {
        $result_type_label = $result_entity->entity->type->entity->label();
        $time_slice = DateTime::createFromFormat(self::DRUPAL_DATE_FORMAT, $event->get(self::SORT_CRITERIA)->value)->format(self::TIME_OPTIONS[$form_state->getValue('series_1_interval')]['slice']);
        foreach ($result_entity->entity->getFields() as $result_field) {
          if (strpos($result_field->getName(), 'data_') === 0) {
            $data_type_label = $result_field->getDataDefinition()->getLabel();
            foreach ($result_field->entity->getFields() as $data_field) {
              if (
                strpos($data_field->getName(), 'field_') === 0 &&
                !in_array($data_field->getName(), self::DATA_FIELDS_BLACKLIST)
              ) {
                $date_field_label = $data_field->getDataDefinition()->getLabel();
                if (isset($series_data[sprintf('%s - %s', $result_type_label, $date_field_label)][$time_slice])) {
                  $series_data[sprintf('%s - %s', $result_type_label, $date_field_label)][$time_slice] += $data_field->value;
                }
                else {
                  $series_data[sprintf('%s - %s', $result_type_label, $date_field_label)][$time_slice] = $data_field->value;
                }
              }
            }
          }
        }
      }
    }
    $chart = new HighChartsChart([
      'chart' => (object) [
        'zoomType' => 'xy',
      ],
      'title' => (object) [
        'text' => $filter->getName(),
      ],
      'tooltip' => [
        'shared' => TRUE,
      ],
      'legend' => [
        'layout' => 'vertical',
        'align' => 'left',
        'x' => 120,
        'verticalAlign' => 'top',
        'y' => 100,
        'floating' => TRUE,
        'backgroundColor' => '#FFFFFF',
      ],
    ]);
    // Calculate series.
    foreach ($series_data as $series_name => $data) {
      $series = [];
      foreach ($categories as $time_slice) {
        $series[$time_slice] = isset($data[$time_slice]) ? $data[$time_slice] : 0;
      }
      $chart->attach(new HighChartsAxis(HighChartsAxis::TYPE_LINE, [
        'turboThreshold' => 0,
        // Axis settings.
        'labels' => [
          'format' => '{value}',
          'style' => (object) [
            'color' => 'Highcharts.getOptions().colors[1]',
          ],
        ],
        'title' => (object) [
          'text' => '',
          'style' => (object) [
            'color' => 'Highcharts.getOptions().colors[1]',
          ],
        ],
        // Series settings.
        'name' => $series_name,
        'type' => $form_state->getValue('series_1_type'),
        'yAxis' => 0,
      ], array_values($series)));
    }
    $category_axis = new HighChartsAxis(HighChartsAxis::TYPE_CATEGORIES, [
      'crosshair' => TRUE,
    ], $categories);
    $chart->attach($category_axis);    
    $response->addCommand(new SettingsCommand([
      'highcharts' => $chart->render(),
    ], TRUE));
    return $response;
  }

}
