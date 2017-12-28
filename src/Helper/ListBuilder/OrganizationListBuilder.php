<?php

namespace Drupal\effective_activism\Helper\ListBuilder;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Helper\AccountHelper;

/**
 * Defines a class to build a listing of Organization entities.
 *
 * @ingroup effective_activism
 */
class OrganizationListBuilder extends EntityListBuilder {

  const THEME_ID = 'organization_list';

  const CACHE_MAX_AGE = Cache::PERMANENT;

  const CACHE_TAGS = [
    Constant::CACHE_TAG_USER,
    Constant::CACHE_TAG_ORGANIZATION,
  ];

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = $this->t('Title');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = $this->l(
      $entity->label(),
      new Url(
        'entity.organization.canonical', [
          'organization' => $entity->id(),
        ]
      )
    );
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $result = [];
    $organizations = AccountHelper::getOrganizations(NULL, FALSE);
    if (!empty($organizations)) {
      $query = $this->getStorage()->getQuery()
        ->sort('title')
        ->condition('id', $organizations, 'IN');
      // Only add the pager if a limit is specified.
      if ($this->limit) {
        $query->pager($this->limit);
      }
      $result = $query->execute();
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['#storage']['entities']['organizations'] = $this->load();
    $build['#theme'] = self::THEME_ID;
    $build['#cache'] = [
      'max-age' => self::CACHE_MAX_AGE,
      'tags' => self::CACHE_TAGS,
    ];
    return $build;
  }

}
