<?php

namespace Drupal\effective_activism\Hook;

use Drupal\effective_activism\Constant;

/**
 * Implements hook_mail().
 */
class MailHook implements HookInterface {

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
    $key = $args['key'];
    $message = &$args['message'];
    $params = $args['params'];
    switch ($key) {

      case Constant::MAIL_KEY_INVITATION_MANAGER:
        $message['subject'] = sprintf('[AFA] Invitation to join %s', $params['organization_label']);
        $message['body'][] = 'Hello';
        $message['body'][] = sprintf('You have been invited to join %s as manager on https://www.activeforanimals.com.', $params['organization_label']);
        $message['body'][] = 'To join, follow these steps:';
        $message['body'][] = '- Create an account at https://www.activeforanimals.com/user using the e-mail address that this e-mail was sent to.';
        $message['body'][] = sprintf('- When logged in, confirm that you want to join %s.', $params['organization_label']);
        break;

      case Constant::MAIL_KEY_INVITATION_ORGANIZER:
        $message['subject'] = sprintf('[AFA] Invitation to join the group \'%s\' of %s', $params['group_label'], $params['organization_label']);
        $message['body'][] = 'Hello';
        $message['body'][] = sprintf('You have been invited to join the group \'%s\' of the organizaiton %s as organizer on https://www.activeforanimals.com.', $params['group_label'], $params['organization_label']);
        $message['body'][] = 'To join, follow these steps:';
        $message['body'][] = '- Create an account at https://www.activeforanimals.com/user using the e-mail address that this e-mail was sent to.';
        $message['body'][] = sprintf('- When logged in, confirm that you want to join \'%s.\'', $params['group_label']);
        break;

    }
  }

}
