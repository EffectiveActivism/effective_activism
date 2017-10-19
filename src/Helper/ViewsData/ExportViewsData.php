<?php

namespace Drupal\effective_activism\Helper\ViewsData;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Export entities.
 */
class ExportViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['export']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Export'),
      'help' => $this->t('The Export ID.'),
    ];

    return $data;
  }

}
