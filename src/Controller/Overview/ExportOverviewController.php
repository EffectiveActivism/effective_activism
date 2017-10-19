<?php

namespace Drupal\effective_activism\Controller\Overview;

use Drupal\Core\Cache\Cache;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Helper\GroupHelper;

/**
 * Controller class for export entities.
 */
class ExportOverviewController extends ListBaseController {

  const THEME_ID = 'group_export_overview';

  const CACHE_MAX_AGE = Cache::PERMANENT;

  const CACHE_TAGS = [
    Constant::CACHE_TAG_USER,
    Constant::CACHE_TAG_EXPORT,
  ];

  /**
   * Returns a render array for the overview page.
   *
   * @return array
   *   A render array.
   */
  public function content() {
    $content['#theme'] = self::THEME_ID;
    $content['#storage']['entities']['group'] = empty($this->entities['group']) ? NULL : $this->entities['group'];
    $content['#storage']['entities']['exports'] = $this->entities['exports'];
    $content['#cache'] = [
      'max-age' => self::CACHE_MAX_AGE,
      'tags' => self::CACHE_TAGS,
    ];
    return $content;
  }

  /**
   * A callback for routes.
   *
   * @param \Drupal\effective_activism\Entity\Group $group
   *   The group to render exports for.
   *
   * @return array
   *   A render array.
   */
  public function routeCallback(Group $group) {
    $content['#theme'] = self::THEME_ID;
    $content['#storage']['entities']['group'] = $group;
    $content['#storage']['entities']['exports'] = GroupHelper::getExportsPaged($group);
    $content['#cache'] = [
      'max-age' => self::CACHE_MAX_AGE,
      'tags' => self::CACHE_TAGS,
    ];
    return $content;
  }

}
