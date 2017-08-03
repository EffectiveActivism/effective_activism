<?php

namespace Drupal\effective_activism\Helper;

use Drupal;
use Drupal\effective_activism\Constant;

/**
 * Provides helper functions for sending e-mail.
 */
class MailHelper {

  /**
   * Validates an address.
   *
   * @param string $address
   *   An address string that needs to be validated against Google Maps API
   *   format.
   *
   * @return bool
   *   Returns TRUE if string is a valid Google Maps address, FALSE if not.
   *   If any connection errors occur, validation returns TRUE.
   */
  public static function send($key, $params, $recipient, $sender) {
    $status = Drupal::service('plugin.manager.mail')->mail(
      Constant::MODULE_NAME,
      $key,
      $recipient,
      Drupal::currentUser()->getPreferredLangcode(),
      $params,
      $sender
    );
    return (isset($status['result']) && $status['result'] === TRUE) ? TRUE : FALSE;
  }

}
