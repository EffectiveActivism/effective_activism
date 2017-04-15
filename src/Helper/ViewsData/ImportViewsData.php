<?php

namespace Drupal\effective_activism\Helper\ViewsData;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Import entities.
 */
class ImportViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['import']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Import'),
      'help' => $this->t('The Import ID.'),
    );

    return $data;
  }

}
