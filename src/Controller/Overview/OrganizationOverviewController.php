<?php

namespace Drupal\effective_activism\Controller\Overview;

use Drupal\effective_activism\Helper\AccountHelper;

/**
 * An overview of organizations.
 */
class OrganizationOverviewController extends ListBaseController {

  const THEME_ID = 'organization_overview';

  const CACHE_MAX_AGE = Cache::PERMANENT;

  const CACHE_TAGS = [
    Constant::CACHE_TAG_USER,
    Constant::CACHE_TAG_ORGANIZATION,
  ];

  /**
   * Returns an array of organizations.
   *
   * @return array
   *   A render array.
   */
  public function view() {
    $content['#theme'] = self::THEME_ID;
    $content['#storage']['organizations'] = $this->entities;
    $content['#cache'] = [
      'max-age' => self::CACHE_MAX_AGE,
      'tags' => self::CACHE_TAGS,
    ];
    return $content;
  }
}
