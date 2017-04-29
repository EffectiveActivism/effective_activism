<?php

namespace Drupal\effective_activism\Helper\ViewsData;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Organization entities.
 */
class OrganizationViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['organization']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Organization'),
      'help' => $this->t('The Organization ID.'),
    ];
    return $data;
  }

}
