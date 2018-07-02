<?php

namespace Drupal\effective_activism\ListBuilder;

use Drupal;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Helper\AccountHelper;
use ReflectionClass;

/**
 * Defines a class to build a listing of Organization entities.
 *
 * @ingroup effective_activism
 */
class OrganizationListBuilder extends EntityListBuilder {

  const CACHE_MAX_AGE = Cache::PERMANENT;

  const CACHE_TAGS = [
    Constant::CACHE_TAG_USER,
    Constant::CACHE_TAG_ORGANIZATION,
  ];

  const DEFAULT_LIMIT = 10;

  /**
   * A pager index to resolve multiple pagers on a page.
   *
   * @var int
   */
  protected $pagerIndex = 0;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage) {
    parent::__construct($entity_type, $storage);
    $this->limit = self::DEFAULT_LIMIT;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort('title');
    // Filter entities for non-admin users.
    if (Drupal::currentUser()->id() !== '1') {
      $organizations = AccountHelper::getOrganizations(NULL, FALSE);
      if (!empty($organizations)) {
        $query->condition('id', $organizations, 'IN');
      }
      else {
        return [];
      }
    }
    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    $result = $query->execute();
    return $result;
  }

  /**
   * Setter to dynamically set limit. See https://www.drupal.org/node/2736377.
   *
   * @var int $limit
   *   The limit to set.
   */
  public function setLimit($limit) {
    $this->limit = $limit;
    return $this;
  }

  /**
   * Sets an index for the pager.
   *
   * @var int $pager_index
   *   The index to set.
   *
   * @return self
   *   This instance.
   */
  public function setPagerIndex($pager_index) {
    $this->pagerIndex = $pager_index;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['#theme'] = (new ReflectionClass($this))->getShortName();
    $build['#storage']['entities']['organizations'] = $this->load();
    $build['#cache'] = [
      'max-age' => self::CACHE_MAX_AGE,
      'tags' => self::CACHE_TAGS,
    ];
    $build['pager'] = [
      '#type' => 'pager',
      '#element' => $this->pagerIndex,
    ];
    return $build;
  }

}
