<?php

namespace Drupal\effective_activism\Helper\ListBuilder;

use DateTime;
use Drupal;
use Drupal\effective_activism\Helper\AccountHelper;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Export entities.
 *
 * @ingroup effective_activism
 */
class ExportListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['created'] = $this->t('Created');
    $header['organization'] = $this->t('Organization');
    $header['filter'] = $this->t('Filter');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['created'] = DateTime::createFromFormat('U', $entity->getCreatedTime())->format('d/m Y H:i');
    $row['organization'] = $this->l(
      $entity->get('organization')->entity->label(),
      new Url(
        'entity.organization.canonical', [
          'organization' => $entity->get('organization')->entity->id(),
        ]
      )
    );
    $row['filter'] = $this->l(
      $entity->filter->entity->getName(),
      new Url(
        'entity.filter.canonical', [
          'filter' => $entity->filter->entity->id(),
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
      ->sort($this->entityType->getKey('id'));
    // Filter entities for non-admin users.
    if (Drupal::currentUser()->id() !== '1') {
      $group_ids = AccountHelper::getGroups(Drupal::currentUser(), FALSE);
      $query->condition('parent', $group_ids, 'IN');
    }
    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    $result = $query->execute();
    return $result;
  }

}
