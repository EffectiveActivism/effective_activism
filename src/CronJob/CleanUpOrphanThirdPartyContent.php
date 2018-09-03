<?php

namespace Drupal\effective_activism\CronJob;

use Drupal;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Helper\ThirdPartyContentHelper;

/**
 * This cron job removes third-party content entities that aren't referenced.
 *
 * Deletes a batch of third-party content entities every time cron is run.
 */
class CleanUpOrphanThirdPartyContent implements CronJobInterface {

  const BATCH_SIZE = 100;

  /**
   * {@inheritdoc}
   */
  public static function run() {
    $third_party_content_to_delete = [];
    $position = Drupal::config('effective_activism.cron')->get('cleanuporphanthirdpartycontent.position');
    $query =  Drupal::entityQuery('third_party_content');
    $third_party_content_ids = $query
      ->range($position, self::BATCH_SIZE)
      ->execute();
    if (!empty($third_party_content_ids)) {
      foreach ($third_party_content_ids as $id) {
        $query = Drupal::entityQuery('event');
        $number_of_references = $query
          ->condition('third_party_content', $id)
          ->count()
          ->execute();
        if ((int) $number_of_references === 0) {
          // Set unused third-party content for deletion.
          Drupal::logger('effective_activism')->notice(sprintf('Deleted unused third-party content with id: %d', $id));
          $third_party_content_to_delete[] = $id;
        }
      }
      Drupal::configFactory()->getEditable('effective_activism.cron')->set('cleanuporphanthirdpartycontent.position', $position + self::BATCH_SIZE)->save();
    }
    else {
      // Reset position as there are no more third-party content.
      Drupal::configFactory()->getEditable('effective_activism.cron')->set('cleanuporphanthirdpartycontent.position', 0)->save();
    }
    // Delete all unreferenced third-party content in this batch.
    $third_party_content_to_delete = array_unique($third_party_content_to_delete);
    if (!empty($third_party_content_to_delete)) {
      entity_delete_multiple('third_party_content', $third_party_content_to_delete);
    }
  }

}
