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
 * Defines a class to build a listing of Export entities.
 *
 * @ingroup effective_activism
 */
class ExportListBuilder extends EntityListBuilder {

  const CACHE_MAX_AGE = Cache::PERMANENT;

  const CACHE_TAGS = [
    Constant::CACHE_TAG_USER,
    Constant::CACHE_TAG_EXPORT,
  ];

  const DEFAULT_LIMIT = 10;

  const DEFAULT_SORTING_PREFERENCE = FALSE;

  const DEFAULT_EMPTY_TEXT = 'No exports created yet.';

  /**
  * Empty text.
  *
  * @var string
  */
  protected $emptyText;

  /**
   * Group.
   *
   * @var \Drupal\effective_activism\Entity\Group
   */
  protected $group;

  /**
   * The organization that the exports belongs to.
   *
   * @var \Drupal\effective_activism\Entity\Organization
   */
  protected $organization;

  /**
   * Sorting preference.
   *
   * @var bool
   */
  protected $sortAsc;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, Organization $organization = NULL, Group $group = NULL) {
    parent::__construct($entity_type, $storage);
    $this->organization = empty($organization) ? Drupal::request()->get('organization') : $organization;
    $this->group = empty($group) ? Drupal::request()->get('group') : $group;
    $this->emptyText = self::DEFAULT_EMPTY_TEXT;
    $this->limit = self::DEFAULT_LIMIT;
    $this->sortAsc = self::DEFAULT_SORTING_PREFERENCE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery();
    if (isset($this->group)) {
      $query->condition('parent', $this->group->id());
    }
    else {
      $query->condition('organization', $this->organization->id());
      $query->notExists('parent');
    }
    // Sorting preference.
    if ($this->sortAsc) {
      $query->sort('created', 'ASC');
    }
    else {
      $query->sort('created', 'DESC');
    }
    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    $result = $query->execute();
    return $result;
  }

  /**
   * Set the empty message when there is nothing to list.
   *
   * @var string $empty
   *   The empty text to set.
   *
   * @return self
   *   This instance.
   */
  public function setEmpty($empty) {
    $this->emptyText = $empty;
    return $this;
  }

  /**
   * Setter to dynamically set limit. See https://www.drupal.org/node/2736377.
   *
   * @var int $limit
   *   The limit to set.
   *
   * @return self
   *   This instance.
   */
  public function setLimit($limit) {
    $this->limit = $limit;
    return $this;
  }

  /**
   * Determine if sorting by start date should be ascending or descending.
   *
   * @var bool $preference
   *   Whether or not to sort start date ascending.
   *
   * @return self
   *   This instance.
   */
  public function setSortAsc($preference) {
    $this->sortAsc = $preference;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['#theme'] = (new ReflectionClass($this))->getShortName();
    $build['#storage']['entities']['organization'] = $this->organization;
    $build['#storage']['entities']['group'] = $this->group;
    $build['#storage']['entities']['exports'] = $this->load();
    $build['content']['empty'] = $this->emptyText;
    $build['#cache'] = [
      'max-age' => self::CACHE_MAX_AGE,
      'tags' => self::CACHE_TAGS,
    ];
    $build['pager'] = ['#type' => 'pager'];
    return $build;
  }

}
