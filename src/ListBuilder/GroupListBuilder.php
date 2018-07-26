<?php

namespace Drupal\effective_activism\ListBuilder;

use Drupal;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Url;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Helper\OrganizationHelper;
use Drupal\effective_activism\Helper\PathHelper;
use ReflectionClass;

/**
 * Defines a class to build a listing of Group entities.
 *
 * @ingroup effective_activism
 */
class GroupListBuilder extends EntityListBuilder {

  const CACHE_MAX_AGE = Cache::PERMANENT;

  const CACHE_TAGS = [
    Constant::CACHE_TAG_USER,
    Constant::CACHE_TAG_GROUP,
  ];

  const DEFAULT_LIMIT = 10;

  /**
   * The organization that the groups belongs to.
   *
   * @var \Drupal\effective_activism\Entity\Organization
   */
  protected $organization;

  /**
   * A pager index to resolve multiple pagers on a page.
   *
   * @var int
   */
  protected $pagerIndex = 0;

  /**
   * Whether to display map or not.
   *
   * @var bool
   */
  protected $displayMap = TRUE;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, Organization $organization = NULL) {
    parent::__construct($entity_type, $storage);
    $this->organization = empty($organization) ? Drupal::request()->get('organization') : $organization;
    $this->limit = self::DEFAULT_LIMIT;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery();
    $query
      ->sort('title')
      ->condition('organization', $this->organization->id());
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
   *
   * @return self
   *   This instance.
   */
  public function setLimit($limit) {
    $this->limit = $limit;
    return $this;
  }

  /**
   * Hide map display of groups.
   *
   * @return self
   *   This instance.
   */
  public function hideMap() {
    $this->displayMap = FALSE;
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
   * Build a map of groups.
   *
   * @return array
   *   Parameters for a map of groups.
   */
  public function getMap() {
    $map = [];
    foreach (OrganizationHelper::getGroups(Drupal::request()->get('organization')) as $group) {
      $map[] = [
        'gps' => [
          'latitude' => $group->location->latitude,
          'longitude' => $group->location->longitude,
        ],
        'title' => $group->label(),
        'description' => $group->description->value,
        'url' => (new Url('entity.group.canonical', [
          'organization' => PathHelper::transliterate($group->organization->entity->label()),
          'group' => PathHelper::transliterate($group->label()),
        ]))->toString(),
      ];
    }
    return $map;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['#theme'] = (new ReflectionClass($this))->getShortName();
    $build['#storage']['entities']['organization'] = $this->organization;
    $build['#storage']['entities']['groups'] = $this->load();
    $build['#attached']['library'][] = 'effective_activism/leaflet';
    $build['#display']['map'] = $this->displayMap;
    if ($this->displayMap === TRUE) {
      $build['#attached']['drupalSettings']['leaflet']['map'] = $this->getMap();
      $build['#attached']['drupalSettings']['leaflet']['key'] = Drupal::config('effective_activism.settings')->get('mapbox_api_key');
    }
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
