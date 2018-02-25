<?php

namespace Drupal\effective_activism\ListBuilder;

use Drupal;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\effective_activism\Constant;
use ReflectionClass;

/**
 * Defines a class to build a listing of Import entities.
 *
 * @ingroup effective_activism
 */
class ImportListBuilder extends EntityListBuilder {

  const CACHE_MAX_AGE = Cache::PERMANENT;

  const CACHE_TAGS = [
    Constant::CACHE_TAG_USER,
    Constant::CACHE_TAG_IMPORT,
  ];

  /**
   * The organization that the imports belongs to.
   *
   * @var \Drupal\effective_activism\Entity\Organization
   */
  protected $organization;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, Organization $organization = NULL, Group $group = NULL) {
    parent::__construct($entity_type, $storage);
    $this->organization = empty($organization) ? Drupal::request()->get('organization') : $organization;
    $this->group = empty($group) ? Drupal::request()->get('group') : $group;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery();
    $query
      ->sort('created')
      ->condition('parent', $this->group->id());
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
   * {@inheritdoc}
   */
  public function render() {
    $build['#theme'] = (new ReflectionClass($this))->getShortName();
    $build['#storage']['entities']['organization'] = $this->organization;
    $build['#storage']['entities']['group'] = $this->group;
    $build['#storage']['entities']['imports'] = $this->load();
    $build['#cache'] = [
      'max-age' => self::CACHE_MAX_AGE,
      'tags' => self::CACHE_TAGS,
    ];
    return $build;
  }

}
