<?php

namespace Drupal\effective_activism\Helper\ListBuilder;

use Drupal;
use Drupal\effective_activism\Helper\AccountHelper;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Filter entities.
 *
 * @ingroup effective_activism
 */
class FilterListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['organization'] = $this->t('Organization');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['name'] = $this->l(
      $entity->getName(),
      new Url(
        'entity.filter.canonical', [
          'filter' => $entity->id(),
        ]
      )
    );
    $row['organization'] = empty($entity->get('organization')->entity) ? '' : $this->l(
      $entity->get('organization')->entity->label(),
      new Url(
        'entity.organization.canonical', [
          'organization' => $entity->get('organization')->entity->id(),
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
    // Filter entities for non-admin users.
    if (Drupal::currentUser()->id() !== '1') {
      $filter_ids = AccountHelper::getFilters(Drupal::currentUser(), FALSE);
      $query->condition('id', $filter_ids, 'IN');
    }
    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    $result = $query->execute();
    return $result;
  }

}
