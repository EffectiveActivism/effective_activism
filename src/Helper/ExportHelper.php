<?php

namespace Drupal\effective_activism\Helper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Entity\Group;

/**
 * Helper functions for querying export.
 */
class ExportHelper {

  /**
   * Validation callback for the CSV export form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state to validate.
   */
  public static function validateCsv(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $gid = $form_state->getValue('parent')[0]['target_id'];
    $group = Group::load($gid);
    if (count(GroupHelper::getEvents(Group::load($gid), 0, 0, FALSE)) === 0) {
      $form_state->setErrorByName('', t('There are no events to export for this group.'));
    }
  }

}
