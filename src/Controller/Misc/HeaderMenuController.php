<?php

namespace Drupal\effective_activism\Controller\Misc;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller class for contact information.
 */
class HeaderMenuController extends ControllerBase {

  const THEME_ID = 'header_menu';

  /**
   * Returns an array of fields for contact information.
   *
   * @return array
   *   A render array.
   */
  public function view() {
    $content['#theme'] = self::THEME_ID;
    return $content;
  }

}
