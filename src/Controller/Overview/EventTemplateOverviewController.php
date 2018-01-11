<?php

namespace Drupal\effective_activism\Controller\Overview;

use Drupal\Core\Cache\Cache;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Helper\OrganizationHelper;

/**
 * Controller class for event template entities.
 */
class EventTemplateOverviewController extends ListBaseController {

  const THEME_ID = 'organization_event_template_overview';

  const CACHE_MAX_AGE = Cache::PERMANENT;

  const CACHE_TAGS = [
    Constant::CACHE_TAG_USER,
    Constant::CACHE_TAG_EVENT_TEMPLATE,
  ];

  /**
   * Returns a render array for the overview page.
   *
   * @return array
   *   A render array.
   */
  public function content() {
    $content['#theme'] = self::THEME_ID;
    $content['#storage']['entities']['organization'] = empty($this->entities['organization']) ? NULL : $this->entities['organization'];
    $content['#storage']['entities']['event_templates'] = $this->entities['event_templates'];
    $content['#cache'] = [
      'max-age' => self::CACHE_MAX_AGE,
      'tags' => self::CACHE_TAGS,
    ];
    return $content;
  }

  /**
   * A callback for routes.
   *
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization to render event templates for.
   *
   * @return array
   *   A render array.
   */
  public function routeCallback(Organization $organization) {
    $content['#theme'] = self::THEME_ID;
    $content['#storage']['entities']['organization'] = $organization;
    $content['#storage']['entities']['event_templates'] = OrganizationHelper::getEventTemplatesPaged($organization);
    $content['#cache'] = [
      'max-age' => self::CACHE_MAX_AGE,
      'tags' => self::CACHE_TAGS,
    ];
    return $content;
  }

}
