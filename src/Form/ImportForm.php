<?php

namespace Drupal\effective_activism\Form;

use Drupal;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Import;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Helper\PathHelper;
use ReflectionClass;

/**
 * Form controller for Import edit forms.
 *
 * @ingroup effective_activism
 */
class ImportForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Organization $organization = NULL, Group $group = NULL, Import $import = NULL) {
    $form = parent::buildForm($form, $form_state);
    $form['#theme'] = (new ReflectionClass($this))->getShortName();
    $entity = $this->entity;
    // Set values from path.
    $form['parent']['widget'][0]['target_id']['#default_value'] = $group;
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
        $form['field_file_csv']['#disabled'] = TRUE;
        break;

      case 'import_icalendar_add_form':
        // Add validation.
        $form['#validate'][] = 'Drupal\effective_activism\Helper\ImportHelper::validateIcalendar';
        break;

      case 'import_icalendar_edit_form':
        // Restrict access to existing import entities.
        $form['field_url']['#disabled'] = TRUE;
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
    $form_state->setRedirect('entity.import.canonical', [
      'organization' => PathHelper::transliterate(Drupal::request()->get('organization')->label()),
      'group' => PathHelper::transliterate(Drupal::request()->get('group')->label()),
      'import' => $entity->id(),
    ]);
  }

}
