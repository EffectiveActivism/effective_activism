<?php

namespace Drupal\effective_activism\ListBuilder;

use DateTime;
use Drupal;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Helper\AccountHelper;

/**
 * Defines a class to build a listing of Export entities.
 *
 * @ingroup effective_activism
 */
class ExportListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  const THEME_ID = 'export_list';

  const CACHE_MAX_AGE = Cache::PERMANENT;

  const CACHE_TAGS = [
    Constant::CACHE_TAG_USER,
    Constant::CACHE_TAG_EXPORT,
  ];

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['created'] = DateTime::createFromFormat('U', $entity->getCreatedTime())->format('d/m Y H:i');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort($this->entityType->getKey('id'));
    // Filter entities for non-admin users.
    if (Drupal::currentUser()->id() !== '1') {
      $organization_ids = AccountHelper::getOrganizations(Drupal::currentUser(), FALSE);
      $query->condition('organization', $organization_ids, 'IN');
    }
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
    $build['#storage']['entities']['exports'] = $this->load();
    $build['#theme'] = self::THEME_ID;
    $build['#cache'] = [
      'max-age' => self::CACHE_MAX_AGE,
      'tags' => self::CACHE_TAGS,
    ];
    return $build;
  }

}
