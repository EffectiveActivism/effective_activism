<?php

namespace Drupal\effective_activism\Helper;

use Drupal;
use Drupal\effective_activism\Constant;

/**
 * Provides helper functions for sending e-mail.
 */
class MailHelper {

  /**
   * Sends an e-mail.
   *
   * @param string $key
   *   The mail key to use.
   * @param array $params
   *   An array of variables to use in composing the e-mail.
   * @param string $recipient
   *   The recipient of the e-mail.
   * @param string $sender
   *   The sender of the e-mail.
   *
   * @return bool
   *   Returns TRUE if Drupal reports a successful send, FALSE otherwise.
   */
  public static function send($key, array $params, $recipient, $sender) {
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
