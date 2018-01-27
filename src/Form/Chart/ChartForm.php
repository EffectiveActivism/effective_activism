<?php

namespace Drupal\effective_activism\Form\Chart;

use DateInterval;
use DatePeriod;
use DateTime;
use Drupal;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Chart\Providers\HighCharts\HighChartsChart;
use Drupal\effective_activism\Chart\Providers\HighCharts\HighChartsAxis;
use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Entity\Filter;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Helper\FilterHelper;
use Drupal\effective_activism\Helper\OrganizationHelper;
use Drupal\effective_activism\Helper\ResultTypeHelper;

/**
 * Form controller for Chart forms.
 *
 * @ingroup effective_activism
 */
class ChartForm extends FormBase {

  const FORM_ID = 'effective_activism_chart';

  const THEME_ID = self::FORM_ID . '-form';

  const AJAX_WRAPPER = 'ajax-chart';

  const TIME_SLICE = 'Y - n/j';

  const TIME_INTERVAL = 'P1D';

  const TIME_MODIFIER = '+1 day';

  const DRUPAL_DATE_FORMAT = 'Y-m-d\TH:i:s';

  const SORT_CRITERIA = 'start_date';

  const CACHE_MAX_AGE = Cache::PERMANENT;

  const CACHE_TAGS = [
    'result',
    'data',
  ];

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
      '#description' => $this->t('The chart type for the first data series.'),
      '#options' => self::CHART_TYPE_OPTIONS,
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
        'callback' => [$this, 'updateChart'],
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
   * Populates the chart element.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form array.
   */
  public function updateChart(array &$form, FormStateInterface $form_state) {
    $filter = Filter::load($form_state->getValue('filter'));
    // Get events.
    $events = FilterHelper::getEvents($filter);
    // Get oldest event.
    $oldest_event = reset($events);
    // Get newest event.
    $newest_event = end($events);
    // Create timesliced array. Add extra time to end date to ensure it is added.
    $period = new DatePeriod(
      DateTime::createFromFormat(self::DRUPAL_DATE_FORMAT, $oldest_event->get(self::SORT_CRITERIA)->value),
      new DateInterval(self::TIME_INTERVAL),
      DateTime::createFromFormat(self::DRUPAL_DATE_FORMAT, $newest_event->get(self::SORT_CRITERIA)->value)->modify(self::TIME_MODIFIER)
    );
    $categories = [];
    foreach ($period as $date) {
      $categories[] = $date->format(self::TIME_SLICE);
    }
    // Populate categories and data.
    $series_value_label = '';
    $series_data = $participants = []; //array_fill_keys($categories, NULL);
    foreach ($events as $event) {
      foreach ($event->get('results') as $result_entity) {
        $result_type_label = $result_entity->entity->type->entity->label();
        $time_slice = DateTime::createFromFormat(self::DRUPAL_DATE_FORMAT, $event->get(self::SORT_CRITERIA)->value)->format(self::TIME_SLICE);
        $participants[$time_slice] += $result_entity->entity->participant_count->value;
        foreach ($result_entity->entity->getFields() as $result_field) {
          if (strpos($result_field->getName(), 'data_') === 0) {
            $data_type_label = $result_field->getDataDefinition()->getLabel();
            foreach ($result_field->entity->getFields() as $data_field) {
              if (strpos($data_field->getName(), 'field_') === 0) {
                $date_field_label = $data_field->getDataDefinition()->getLabel();
                $series_data[sprintf('%s - %s', $result_type_label, $date_field_label)][$time_slice] += $data_field->value;
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
      'subtitle' => (object) [
        'text' => $series_name,
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
    $form['chart']['#attached']['drupalSettings']['highcharts'] = $chart->render();
    return $form['chart'];
  }

}
