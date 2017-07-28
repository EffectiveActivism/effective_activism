<?php

namespace Drupal\effective_activism\Controller\Overview;

use Drupal\Core\Cache\Cache;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Helper\GroupHelper;
use Drupal\effective_activism\Helper\InvitationHelper;

/**
 * Controller class for invitations.
 */
class InvitationOverviewController extends ListBaseController {

  const THEME_ID = 'invitation_overview';

  const CACHE_MAX_AGE = Cache::PERMANENT;

  const CACHE_TAGS = [
    Constant::CACHE_TAG_GROUP,
  ];

  /**
   * Returns a render array for the overview page.
   *
   * @return array
   *   A render array.
   */
  public function content(array $invitations) {
    $content['#theme'] = self::THEME_ID;
    $content['#storage']['invitations'] = $invitations;
    $content['#cache'] = [
      'max-age' => self::CACHE_MAX_AGE,
      'tags' => self::CACHE_TAGS,
    ];
    return $content;
  }

}
