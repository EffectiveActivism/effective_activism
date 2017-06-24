<?php

namespace Drupal\effective_activism\Form\Import;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Import edit forms.
 *
 * @ingroup effective_activism
 */
class ImportForm extends ContentEntityForm {

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
      case 'import_csv_add_form':
        // Add validation.
        $form['#validate'][] = 'Drupal\effective_activism\Helper\ImportHelper::validateCsv';
        break;

      case 'import_csv_edit_form':
        // Restrict access to existing import entities.
        $form['parent']['#disabled'] = TRUE;
        $form['field_file_csv']['#disabled'] = TRUE;
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
        drupal_set_message($this->t('Created the import.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the import.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.import.canonical', ['import' => $entity->id()]);
  }

}
