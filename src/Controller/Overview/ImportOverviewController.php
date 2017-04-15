<?php

namespace Drupal\effective_activism\Controller\Overview;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Helper\GroupHelper;

/**
 * Controller class for group events.
 */
class ImportOverviewController extends ListBaseController {

  const THEME_ID = 'group_import_overview';

  const CACHE_MAX_AGE = Cache::PERMANENT;

  const CACHE_TAGS = [
    Constant::CACHE_TAG_USER,
    Constant::CACHE_TAG_IMPORT,
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
    $content['#storage']['entities']['imports'] = $this->entities['imports'];
    $content['#cache'] = [
      'max-age' => self::CACHE_MAX_AGE,
      'tags' => self::CACHE_TAGS,
    ];
    return $content;
  }

  /**
   * A callback for routes.
   *
   * @param Group $group
   *
   * @return array
   *   A render array.
   */
  public function routeCallback(Group $group) {
    $content['#theme'] = self::THEME_ID;
    $content['#storage']['entities']['group'] = $group;
    $content['#storage']['entities']['imports'] = GroupHelper::getImports($group);
    $content['#cache'] = [
      'max-age' => self::CACHE_MAX_AGE,
      'tags' => self::CACHE_TAGS,
    ];
    return $content;
  }

}
