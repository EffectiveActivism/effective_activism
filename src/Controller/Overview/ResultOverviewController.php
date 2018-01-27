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
use Drupal\effective_activism\Entity\Filter;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Helper\FilterHelper;
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
   * A callback for routes.
   *
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization to render results for.
   *
   * @return array
   *   A render array.
   */
  public function content(Organization $organization) {
    $filter = isset($filter) ? $filter : (empty($this->entities['filter']) ? NULL : $this->entities['filter']);
    if (empty($filter)) {
      $filter = Filter::load(6);
    }
    $content['#attached']['library'][] = 'effective_activism/highcharts';
    $content['#attached']['drupalSettings']['highcharts'] = $this->renderChart($filter);
    $content['#theme'] = self::THEME_ID;
    $content['#cache'] = [
      'max-age' => self::CACHE_MAX_AGE,
      'tags' => self::CACHE_TAGS,
    ];
    return $content;
  }

}
