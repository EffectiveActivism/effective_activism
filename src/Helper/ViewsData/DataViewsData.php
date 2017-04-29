<?php

namespace Drupal\effective_activism\Helper\ViewsData;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Data entities.
 */
class DataViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['data']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Data'),
      'help' => $this->t('The Data ID.'),
    ];
    return $data;
  }

}
