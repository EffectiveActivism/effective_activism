<?php

namespace Drupal\effective_activism\Helper\ListBuilder;

use Drupal;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Helper\AccountHelper;

/**
 * Defines a class to build a listing of event template entities.
 *
 * @ingroup effective_activism
 */
class EventTemplateListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  const THEME_ID = 'event_template_list';

  const CACHE_MAX_AGE = Cache::PERMANENT;

  const CACHE_TAGS = [
    Constant::CACHE_TAG_USER,
    Constant::CACHE_TAG_EVENT_TEMPLATE,
  ];

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['name'] = $this->l(
      $entity->getName(),
      new Url(
        'entity.event_template.canonical', [
          'event_template' => $entity->id(),
        ]
      )
    );
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort('name');
    // Event template entities for non-admin users.
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
    $build['#storage']['entities']['event_templates'] = $this->load();
    $build['#theme'] = self::THEME_ID;
    $build['#cache'] = [
      'max-age' => self::CACHE_MAX_AGE,
      'tags' => self::CACHE_TAGS,
    ];
    return $build;
  }

}

