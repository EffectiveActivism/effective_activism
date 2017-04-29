<?php

namespace Drupal\effective_activism\Helper\ViewsData;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Person entities.
 */
class PersonViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['person']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Person'),
      'help' => $this->t('The Person ID.'),
    ];
    return $data;
  }

}
