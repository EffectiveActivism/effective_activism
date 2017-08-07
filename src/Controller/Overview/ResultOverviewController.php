<?php

namespace Drupal\effective_activism\Controller\Overview;

use DateInterval;
use DatePeriod;
use DateTime;
use Drupal;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\effective_activism\Chart\Providers\HighCharts\HighChartsChart;
use Drupal\effective_activism\Chart\Providers\HighCharts\HighChartsAxis;
use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Entity\Group;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ResultOverView Controller class.
 */
class ResultOverviewController extends ControllerBase {

  const TIME_SLICE = 'Y - M';

  const TIME_INTERVAL = 'P1M';

  const DRUPAL_DATE_FORMAT = 'Y-m-d\TH:i:s';

  const SORT_CRITERIA = 'start_date';

  const THEME_ID = 'result_overview';

  const CACHE_MAX_AGE = Cache::PERMANENT;

  const CACHE_TAGS = [
    'result',
    'data',
  ];

  /**
   * Query factory definition.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entityQuery
   *   The EntityQuery factory.
   */
  public function __construct(QueryFactory $entityQuery) {
    $this->entityQuery = $entityQuery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * Renders a chart.
   *
   * @param \Drupal\effective_activism\Entity\Group $group
   *   The group to render a chart for.
   *
   * @return object|bool
   *   A chart object or FALSE if there are less than two events.
   */
  public function renderChart(Group $group) {
    $options = [
      'type' => 'column',
      'title' => t('Leaflets'),
      'yaxis_title' => t('Running total'),
      'yaxis_min' => '',
      'yaxis_max' => '',
      'xaxis_title' => t('Months'),
      'secondary_yaxis' => [
        'yaxis_title' => t('Average leaflets'),
        'opposite' => TRUE,
      ],
    ];
    // Get oldest event.
    $query = $this->entityQuery->get('event');
    $result = $query
      ->sort(self::SORT_CRITERIA, 'ASC')
      ->range(0, 1)
      ->condition('parent', $group->id())
      ->execute();
    $oldest_event = Event::load(array_pop($result));
    // Get newest event.
    $query = Drupal::entityQuery('event');
    $result = $query
      ->sort(self::SORT_CRITERIA, 'DESC')
      ->range(0, 1)
      ->condition('parent', $group->id())
      ->execute();
    $newest_event = Event::load(array_pop($result));
    // If there a too few events, skip the chart.
    if ($oldest_event === $newest_event) {
      return FALSE;
    }
    // Create timesliced array. Add one month to end date to ensure it is added.
    $period = new DatePeriod(
      DateTime::createFromFormat(self::DRUPAL_DATE_FORMAT, $oldest_event->get(self::SORT_CRITERIA)->value),
      new DateInterval(self::TIME_INTERVAL),
      DateTime::createFromFormat(self::DRUPAL_DATE_FORMAT, $newest_event->get(self::SORT_CRITERIA)->value)->modify('+1 month')
    );
    $categories = [];
    foreach ($period as $date) {
      $categories[] = $date->format(self::TIME_SLICE);
    }
    // Get events.
    $query = Drupal::entityQuery('event');
    $result = $query
      ->sort(self::SORT_CRITERIA, 'ASC')
      ->condition('parent', $group->id())
      ->execute();
    $events = Event::loadMultiple($result);
    // Populate categories and data.
    $leaflets = $participants = array_fill_keys($categories, 0);
    foreach ($events as $event) {
      foreach ($event->get('results') as $result_entity) {
        if (isset($result_entity->entity->data_leaflets)) {
          $time_slice = DateTime::createFromFormat(self::DRUPAL_DATE_FORMAT, $event->get(self::SORT_CRITERIA)->value)->format(self::TIME_SLICE);
          $leaflets[$time_slice] += $result_entity->entity->data_leaflets->entity->field_leaflets->value;
          $participants[$time_slice] += $result_entity->entity->participant_count->value;
        }
      }
    }
    // Calculate average and running total.
    $average = $running_total = [];
    $running_total_tally = 0;
    foreach ($categories as $time_slice) {
      $average[$time_slice] = $participants[$time_slice] > 0 ? round($leaflets[$time_slice] / $participants[$time_slice], 0) : 0;
      $running_total[$time_slice] = $running_total_tally = $leaflets[$time_slice] + $running_total_tally;
    }
    // Custom chart.
    $chart = new HighChartsChart([
      'chart' => (object) [
        'zoomType' => 'xy',
      ],
      'title' => (object) [
        'text' => t('Leaflets average take-rate and running total'),
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
        'format' => '{value}',
        'style' => (object) [
          'color' => 'Highcharts.getOptions().colors[1]',
        ],
      ],
      'title' => (object) [
        'text' => 'Average leaflets taken per participant',
        'style' => (object) [
          'color' => 'Highcharts.getOptions().colors[1]',
        ],
      ],
      // Series settings.
      'name' => 'Leaflets taken per participant',
      'type' => 'column',
      'yAxis' => 0,
      'tooltip' => (object) [
        'valueSuffix' => ' leaflets',
      ],
    ], array_values($average));
    $axis_2 = new HighChartsAxis(HighChartsAxis::TYPE_COLUMN, [
      // Axis settings.
      'labels' => [
        'format' => '{value}',
        'style' => (object) [
          'color' => 'Highcharts.getOptions().colors[0]',
        ],
      ],
      'title' => (object) [
        'text' => 'Running total',
        'style' => (object) [
          'color' => 'Highcharts.getOptions().colors[0]',
        ],
      ],
      'opposite' => TRUE,
      // Series settings.
      'name' => 'Running total',
      'type' => 'line',
      'yAxis' => 1,
      'tooltip' => (object) [
        'valueSuffix' => ' leaflets',
      ],
    ], array_values($running_total));
    $category_axis = new HighChartsAxis(HighChartsAxis::TYPE_CATEGORIES, [
      'crosshair' => TRUE,
    ], $categories);
    $chart->attach($axis_1);
    $chart->attach($axis_2);
    $chart->attach($category_axis);
    return $chart->render();
  }

  /**
   * A callback for routes.
   *
   * @param \Drupal\effective_activism\Entity\Group $group
   *   The group to render results for.
   *
   * @return array
   *   A render array.
   */
  public function content(Group $group = NULL) {
    $group = isset($group) ? $group : (empty($this->entities['group']) ? NULL : $this->entities['group']);
    $content['#attached']['library'][] = 'effective_activism/highcharts';
    $content['#attached']['drupalSettings']['highcharts'] = $this->renderChart($group);
    $content['#theme'] = self::THEME_ID;
    $content['#cache'] = [
      'max-age' => self::CACHE_MAX_AGE,
      'tags' => self::CACHE_TAGS,
    ];
    return $content;
  }

}
