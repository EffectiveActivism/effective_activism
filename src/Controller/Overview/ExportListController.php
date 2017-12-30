<?php

namespace Drupal\effective_activism\Controller\Overview;

use Drupal\Core\Cache\Cache;
use Drupal\effective_activism\Constant;

/**
 * Base controller class for lists.
 */
class ExportListController extends ListBaseController {

  const THEME_ID = 'export_list';

  const CACHE_MAX_AGE = Cache::PERMANENT;

  const CACHE_TAGS = [
    Constant::CACHE_TAG_USER,
    Constant::CACHE_TAG_EXPORT,
  ];

  /**
   * How many exports to display.
   */
  const EXPORT_DISPLAY_LIMIT = 10;

  /**
   * Returns an array of event fields.
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