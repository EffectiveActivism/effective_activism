<?php

namespace Drupal\effective_activism\CronJob;

use Drupal;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Entity\ThirdPartyContent;
use Drupal\effective_activism\ThirdPartyApi\DarkSky;
use Drupal\effective_activism\ThirdPartyApi\ThirdPartyApiException;

/**
 * This cron job populates existing third party content entities.
 *
 * Processed a batch of third party content entities every time cron is run.
 */
class PopulateThirdPartyContent {

  const BATCH_SIZE = 100;

  /**
   * @{inheritdoc}
   */
  public static function run() {
    // Find third-party content that hasn't been updated.
    $query = Drupal::entityQuery('third_party_content');
    $query
      ->condition('status', 0)
      ->range(NULL, self::BATCH_SIZE);
    $third_party_content_ids = $query->execute();
    if (!empty($third_party_content_ids)) {
      foreach ($third_party_content_ids as $id) {
        $third_party_content = ThirdPartyContent::load($id);
        $api = NULL;
        switch ($third_party_content->getType()) {
          case Constant::THIRD_PARTY_CONTENT_TYPE_WEATHER_INFORMATION:
            $api = new DarkSky($third_party_content);
            break;

        }
        // Populate entity with API data.
        try {
          $api->request();
        }
        catch (ThirdPartyApiException $exception) {
          Drupal::logger('effective_activism')->warning(sprintf('ThirdPartyContent id: %d Message: %s', $third_party_content->id(), $exception->getMessage()));
        }
      }
    }
  }
}
