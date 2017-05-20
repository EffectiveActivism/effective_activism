<?php

namespace Drupal\effective_activism\Helper\ViewsData;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for ThirdPartyContent entities.
 */
class ThirdPartyContentViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['third_party_content']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Third-party content'),
      'help' => $this->t('The third-party content ID.'),
    ];
    return $data;
  }

}
