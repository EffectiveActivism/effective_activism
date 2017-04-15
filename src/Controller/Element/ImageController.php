<?php

namespace Drupal\effective_activism\Controller\Element;

use Drupal;
use Drupal\Core\Url;

/**
 * Controller class for image elements.
 */
class ImageController extends ElementBaseController {

  const LOGO_200X200 = 'logo_200x200';
  const LOGO_110X110 = 'logo_110x110';
  const LOGO_50X50 = 'logo_50x50';

  /**
   * Returns a render array for an image.
   *
   * @param string $uri
   *   The field to process.
   * @param string $element_name
   *   The name of the element.
   * @param string $image_style
   *   The image style to display the image with.
   *
   * @return array
   *   A render array.
   */
  public function view($uri, $element_name, $image_style = NULL, Url $url = NULL) {
    $image = \Drupal::service('image.factory')->get($uri);
    if (!$image->isValid()) {
      return [];
    }
    $content = $this->getContainer([
      'image',
      'view',
      sprintf(self::ELEMENT_CLASS_FORMAT, $element_name),
    ]);
    if (!empty($image_style)) {
      $element = [
        '#theme' => 'image_style',
        '#width' =>  $image->getWidth(),
        '#height' => $image->getHeight(),
        '#uri' => $uri,
        '#style_name' => $image_style,
      ];
    }
    else {
      $element = [
        '#theme' => 'image',
        '#width' =>  $image->getWidth(),
        '#height' => $image->getHeight(),
        '#uri' => $uri,
      ];
    }
    if (!empty($url)) {
      $content['element'] = [
        '#type' => 'markup',
        '#markup' => Drupal::l(
          $element,
          $url
        ),
      ];
    }
    else {
      $content['element'] = $element;
    }
    return $content;
  }
}
