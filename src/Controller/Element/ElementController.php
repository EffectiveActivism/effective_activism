<?php

namespace Drupal\effective_activism\Controller\Element;

use Drupal;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Url;

/**
 * Controller wrapper class for render elements.
 */
class ElementController extends ElementBaseController {

  /**
   * Returns a render array for an element.
   *
   * @param array $field
   *   The field to process.
   * @param string $field_name
   *   The field name.
   *
   * @return array
   *   A render array.
   */
  public function view($text, $element_name, Url $url = NULL) {
    $content = $this->getContainer([
      'view',
      sprintf(self::ELEMENT_CLASS_FORMAT, $element_name),
    ]);
    if (empty($url)) {
      $content['element'] = [
        '#type' => 'markup',
        '#markup' => $text,
      ];
    }
    else {
      $content['element'] = [
        '#type' => 'markup',
        '#markup' => Drupal::l(
          $text,
          $url
        ),
      ];
    }
    return $content;
  }
}
