<?php

namespace Drupal\effective_activism\CronJob;

/**
 * An interface for cron jobs.
 */
interface CronJobInterface {

  /**
   * Run the job.
   */
  public static function run();

}
