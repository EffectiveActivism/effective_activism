<?php

namespace Drupal\effective_activism\Form\Export;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Export edit forms.
 *
 * @ingroup effective_activism
 */
class ExportForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;
    // Hide fields.
    $form['user_id']['#attributes']['class'][] = 'hidden';
    $form['revision_log_message']['#attributes']['class'][] = 'hidden';
    // Entity bundle specializations.
    $build_info = $form_state->getBuildInfo();
    switch ($build_info['form_id']) {
      case 'export_csv_add_form':
        // Restrict access to new export entities.
        $form['field_file_csv']['#disabled'] = TRUE;
        $form['field_file_csv']['#attributes']['class'][] = 'hidden';
        // Add validation.
        $form['#validate'][] = 'Drupal\effective_activism\Helper\ExportHelper::validateCsv';
        break;

      case 'export_csv_edit_form':
        // Restrict access to existing export entities.
        $form['organization']['#disabled'] = TRUE;
        $form['field_file_csv']['#disabled'] = TRUE;
        $form['filter']['#disabled'] = TRUE;
        break;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->setNewRevision();
    $status = parent::save($form, $form_state);
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the export.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the export.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.export.canonical', ['export' => $entity->id()]);
  }

}
