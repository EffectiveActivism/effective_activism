<?php

namespace Drupal\effective_activism\Helper\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Data entities.
 *
 * @ingroup effective_activism
 */
class DataListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Data ID');
    $header['type'] = $this->t('Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    if ($entity->access('view', \Drupal::currentUser())) {
      $entity_bundles = entity_get_bundles($entity->getEntityTypeId());
      $row['id'] = $entity->id();
      $row['type'] = $this->l(
        $entity_bundles[$entity->bundle()]['label'],
        new Url(
          'entity.data.edit_form', array(
            'data' => $entity->id(),
          )
        )
      );
      return $row + parent::buildRow($entity);
    }
  }

}
