<?php

namespace Drupal\effective_activism\Helper\ListBuilder;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Helper\AccountHelper;

/**
 * Provides a listing of Result type entities.
 */
class ResultTypeListBuilder extends ConfigEntityListBuilder {

  const THEME_ID = 'result_type_overview';

  const CACHE_MAX_AGE = Cache::PERMANENT;

  const CACHE_TAGS = [
    Constant::CACHE_TAG_USER,
    Constant::CACHE_TAG_RESULT_TYPE,
  ];

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      //->sort('label')
      ->sort('organization');
    $organization_ids = AccountHelper::getManagedOrganizations(NULL, FALSE);
    $query->condition('organization', $organization_ids, 'IN');
    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    $result = $query->execute();
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['#theme'] = self::THEME_ID;
    $build['#storage']['result_types'] = $this->load();
    $build['#cache'] = [
      'max-age' => self::CACHE_MAX_AGE,
      'tags' => self::CACHE_TAGS,
    ];
    return $build;
  }

}
