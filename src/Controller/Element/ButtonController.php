<?php

namespace Drupal\effective_activism\Controller\Element;

use Drupal;
use Drupal\Core\Url;

/**
 * Controller wrapper class for render elements.
 */
class ButtonController extends ElementBaseController {

  /**
   * Returns a render array for an element.
   *
   * @param string $text
   *   The button text.
   * @param string $element_name
   *   The element name.
   * @param \Drupal\Core\Url $url
   *   The url to link to.
   *
   * @return array
   *   A render array.
   */
  public function view($text, $element_name, Url $url) {
    $content = $this->getContainer([
      'view',
      'button',
      sprintf(self::ELEMENT_CLASS_FORMAT, $element_name),
    ]);
    if (empty($url)) {
      $content['element'] = $text;
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
