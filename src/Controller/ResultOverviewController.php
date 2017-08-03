<?php

namespace Drupal\effective_activism\Controller;

use DateInterval;
use DatePeriod;
use DateTime;
use Drupal;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\effective_activism\Chart\Providers\HighCharts\HighChartsChart;
use Drupal\effective_activism\Chart\Providers\HighCharts\HighChartsAxis;
use Drupal\effective_activism\Entity\Event;

class ResultOverviewController extends ControllerBase {

  const TIME_SLICE = 'Y - M';

  const TIME_INTERVAL = 'P1M';

  const THEME_ID = 'result_overview';

  const CACHE_MAX_AGE = Cache::PERMANENT;

  const CACHE_TAGS = [
    'result',
  ];

  public function content() {
    $options = [
      'type' => 'column',
      'title' => t('Leaflets'),
      'yaxis_title' => t('Leaflets'),
      'yaxis_min' => '',
      'yaxis_max' => '',
      'xaxis_title' => t('Months'),
      'secondary_yaxis' => [
        'yaxis_title' => t('Participants'),
        'opposite' => TRUE,
      ],
    ];
    // Get oldest event.
    $query = Drupal::entityQuery('event');
    $query->sort('start_date', 'ASC');
    $query->range(1, 1);
    $result = $query->execute();
    $oldest_event = Event::load(array_pop($result));
    // Get newest event.
    $query = Drupal::entityQuery('event');
    $query->sort('start_date', 'DESC');
    $query->range(1, 1);
    $result = $query->execute();
    $newest_event = Event::load(array_pop($result));
    // Create timesliced array.
    $period = new DatePeriod(
      DateTime::createFromFormat('Y-m-d\TH:i:s', $oldest_event->get('start_date')->value),
      new DateInterval(self::TIME_INTERVAL),
      DateTime::createFromFormat('Y-m-d\TH:i:s', $newest_event->get('start_date')->value)
    );
    $categories = [];
    foreach ($period as $date) {
      $categories[] = $date->format(self::TIME_SLICE);
    }
    // Get events.
    $query = Drupal::entityQuery('event');
    $query->sort('start_date', 'ASC');
    $result = $query->execute();
    $events = Event::loadMultiple($result);
    // Populate categories and data.
    $leaflets = $participants = array_fill_keys($categories, 0);
    foreach ($events as $event) {
      $time_slice = DateTime::createFromFormat('Y-m-d\TH:i:s', $event->get('start_date')->value)->format(self::TIME_SLICE);
      $leaflets[$time_slice] = isset($leaflets[$time_slice]) ? $leaflets[$time_slice] : 0;
      $participants[$time_slice] = isset($participants[$time_slice]) ? $participants[$time_slice] : 0;
      $participant_array = [];
      foreach ($event->get('results') as $result_entity) {
        if (isset($result_entity->entity->data_leaflets)) {
          $leaflets[$time_slice] += $result_entity->entity->data_leaflets->entity->field_leaflets->value;
        }
        if (isset($result_entity->entity->participant_count->value)) {
          $participant_array[] = $result_entity->entity->participant_count->value;
        }
      }
      $participants[$time_slice] = count($participant_array) === 0 ? 0 : array_sum($participant_array) / count($participant_array);
    }
    // Custom chart.
    $chart = new HighChartsChart([
      'chart' => (object) [
        'zoomType' => 'xy',
      ],
      'title' => (object) [
        'text' => t('Leaflets and participants'),
      ],
      'subtitle' => (object) [
        'text' => t('Shown across months'),
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
    $axis_1 = new HighChartsAxis(HighChartsAxis::TYPE_LINE, [
      // Axis settings.
      'labels' => [
        'format' => '{value} leaflets',
        'style' => (object) [
          'color' => 'Highcharts.getOptions().colors[1]',
        ],
      ],
      'title' => (object) [
        'text' => 'Leaflets taken',
        'style' => (object) [
          'color' => 'Highcharts.getOptions().colors[1]',
        ],
      ],
      // Series settings.
      'name' => 'Leaflets',
      'type' => 'line',
      'yAxis' => 1,
      'tooltip' => (object) [
        'valueSuffix' => ' leaflets',
      ],
    ], array_values($leaflets));
    $axis_2 = new HighChartsAxis(HighChartsAxis::TYPE_COLUMN, [
      // Axis settings.
      'labels' => [
        'format' => '{value} participants',
        'style' => (object) [
          'color' => 'Highcharts.getOptions().colors[0]',
        ],
      ],
      'title' => (object) [
        'text' => 'Participants',
        'style' => (object) [
          'color' => 'Highcharts.getOptions().colors[0]',
        ],
      ],
      'opposite' => TRUE,
      // Series settings.
      'name' => 'Participants',
      'type' => 'column',
      'tooltip' => (object) [
        'valueSuffix' => ' people',
      ],
    ], array_values($participants));
    $category_axis = new HighChartsAxis(HighChartsAxis::TYPE_CATEGORIES, [
      'crosshair' => true,
    ], $categories);
    $chart->attach($axis_1);
    $chart->attach($axis_2);
    $chart->attach($category_axis);
    $content['#attached']['library'][] = 'effective_activism/highcharts';
    $content['#attached']['drupalSettings']['highcharts'] = $chart->render();
    $content['#theme'] = self::THEME_ID;
    $content['#cache'] = [
      'max-age' => self::CACHE_MAX_AGE,
      'tags' => self::CACHE_TAGS,
    ];
    return $content;
  }

}
