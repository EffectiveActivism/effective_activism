<?php

namespace Drupal\effective_activism\Helper\ListBuilder;

use Drupal\effective_activism\Helper\AccountHelper;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Group entities.
 *
 * @ingroup effective_activism
 */
class GroupListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = $this->t('Title');
    $header['organization'] = $this->t('Organization');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = $this->l(
      $entity->label(),
      new Url(
        'entity.group.canonical', [
          'group' => $entity->id(),
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
      ->sort($this->entityType->getKey('id'));
    // Filter entities for non-admin users.
    if (\Drupal::currentUser()->id() !== '1') {
      $group_ids = AccountHelper::getGroups(\Drupal::currentUser(), FALSE);
      $query->condition('id', $group_ids, 'IN');
    }
    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    $result = $query->execute();
    return $result;
  }

}
