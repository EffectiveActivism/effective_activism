<?php

namespace Drupal\effective_activism\Controller\Element;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Field\FieldItemList;

/**
 * Base controller class for render elements.
 */
class ElementBaseController extends ControllerBase {

  const ELEMENT_CLASS_FORMAT = 'element-%s';

  /**
   * Returns a container element.
   *
   * @param array $classes
   *   The classes to include.
   *
   * @return array
   *   The container render element array.
   */
  protected function getContainer(array $classes) {
    $container = [
      '#type' => 'container',
      '#attributes' => [
        'class' => array_merge(['element'], $classes),
      ]
    ];
    return $container;
  }
}
