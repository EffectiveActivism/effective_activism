<?php

namespace Drupal\effective_activism\Hook;

use Drupal\effective_activism\ThirdPartyApi\ArcGis;
use Drupal\effective_activism\ThirdPartyApi\DarkSky;

/**
 * Implements hook_requirements().
 */
class RequirementsHook implements HookInterface {

  /**
   * An instance of this class.
   *
   * @var HookImplementation
   */
  private static $instance;

  /**
   * {@inheritdoc}
   */
  public static function getInstance() {
    if (!(self::$instance instanceof self)) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * {@inheritdoc}
   */
  public function invoke(array $args) {
    $phase = $args['phase'];
    $requirements = [];
    if ($phase === 'runtime') {
      $requirements['effective_activism-arcgis'] = ArcGis::status();
      $requirements['effective_activism-darksky'] = Darksky::status();
    }
    return $requirements;
  }

}
