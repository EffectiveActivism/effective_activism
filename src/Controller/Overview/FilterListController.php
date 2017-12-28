<?php

namespace Drupal\effective_activism\Controller\Overview;

use Drupal\Core\Cache\Cache;
use Drupal\effective_activism\Constant;

/**
 * Base controller class for lists.
 */
class FilterListController extends ListBaseController {

  const THEME_ID = 'filter_list';

  const CACHE_MAX_AGE = Cache::PERMANENT;

  const CACHE_TAGS = [
    Constant::CACHE_TAG_USER,
    Constant::CACHE_TAG_FILTER,
  ];

  /**
   * How many filters to display.
   */
  const FILTER_DISPLAY_LIMIT = 10;

  /**
   * Returns an array of filter fields.
   *
   * @return array
   *   A render array.
   */
  public function view() {
    $content['#theme'] = self::THEME_ID;
    $content['#storage']['entities'] = $this->entities;
    $content['#cache'] = [
      'max-age' => self::CACHE_MAX_AGE,
      'tags' => self::CACHE_TAGS,
    ];
    return $content;
  }

}
