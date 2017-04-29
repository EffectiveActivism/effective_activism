<?php

namespace Drupal\effective_activism\Controller\Overview;

use Drupal\Core\Controller\ControllerBase;

/**
 * Base controller class for lists.
 */
class ListBaseController extends ControllerBase {

  /**
   * An array of entity objects.
   */
  protected $entities;

  /**
   * Constructor.
   */
  public function __construct(array $entities = NULL) {
    $this->entities = $entities;
  }

}
