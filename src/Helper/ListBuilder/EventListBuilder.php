<?php

namespace Drupal\effective_activism\Helper\ListBuilder;

use Drupal;
use Drupal\effective_activism\Helper\AccountHelper;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Event entities.
 *
 * @ingroup effective_activism
 */
class EventListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['group'] = $this->t('Group');
    $header['date'] = $this->t('Date');
    $header['start_time'] = $this->t('Start time');
    $header['end_time'] = $this->t('End time');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['group'] = $this->l(
      $entity->get('parent')->entity->label(),
      new Url('entity.group.canonical', [
        'group' => $entity->get('parent')->entity->id(),
      ])
    );
    $row['date'] = \DateTime::createFromFormat('Y-m-d\TH:i:s', $entity->get('start_date')->value)->format('d/m Y');
    $row['start_time'] = \DateTime::createFromFormat('Y-m-d\TH:i:s', $entity->get('start_date')->value)->format('H:i');
    $row['end_time'] = \DateTime::createFromFormat('Y-m-d\TH:i:s', $entity->get('end_date')->value)->format('H:i');
    $row['status'] = $entity->isPublished() ? $this->t('Published') : $this->t('Unpublished');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort('start_date');
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
