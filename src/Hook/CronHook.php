<?php

namespace Drupal\effective_activism\Hook;

use Drupal\effective_activism\CronJob\AddThirdPartyContent;
use Drupal\effective_activism\CronJob\CleanUpOrphanThirdPartyContent;
use Drupal\effective_activism\CronJob\PopulateThirdPartyContent;

/**
 * Implements hook_cron().
 */
class CronHook implements HookInterface {

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
    AddThirdPartyContent::run();
    PopulateThirdPartyContent::run();
    CleanUpOrphanThirdPartyContent::run();
  }

}
