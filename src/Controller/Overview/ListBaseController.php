<?php

namespace Drupal\effective_activism\Controller\Overview;

use Drupal\Core\Controller\ControllerBase;

/**
 * Base controller class for lists.
 */
class ListBaseController extends ControllerBase {

  /**
   * @var array
   *   An array of entity objects.
   */
  protected $entities;

  public function __construct(array $entities = NULL) {
    $this->entities = $entities;
  }
}
