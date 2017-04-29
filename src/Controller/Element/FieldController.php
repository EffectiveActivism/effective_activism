<?php

namespace Drupal\effective_activism\Controller\Element;

use Drupal;
use Drupal\Core\Url;
use Drupal\Core\Field\FieldItemList;

/**
 * Controller wrapper class for render elements.
 */
class FieldController extends ElementBaseController {

  /**
   * Returns a render array for an element.
   *
   * @param FieldItemList $field
   *   The field to process.
   * @param Url $url
   *   An optional url to link the element to.
   *
   * @return array
   *   A render array.
   */
  public function view(FieldItemList $field, Url $url = NULL) {
    $content = $this->getContainer([
      'field',
      'view',
      sprintf(self::ELEMENT_CLASS_FORMAT, $field->getName()),
    ]);
    if (empty($url)) {
      $content['element'] = $field->view('full');
    }
    else {
      $content['element'] = [
        '#type' => 'markup',
        '#markup' => Drupal::l(
          $field->view('full'),
          $url
        ),
      ];
    }
    return $content;
  }

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
  public function form(array $field, $field_name) {
    $content = $this->getContainer([
      'field',
      'form',
      sprintf(self::ELEMENT_CLASS_FORMAT, $field_name),
    ]);
    $content['element'][$field_name] = $field;
    return $content;
  }

}
