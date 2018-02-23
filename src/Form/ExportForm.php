<?php

namespace Drupal\effective_activism\Form;

use Drupal;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Entity\Export;
use Drupal\effective_activism\Entity\Filter;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Helper\OrganizationHelper;
use Drupal\effective_activism\Helper\PathHelper;
use ReflectionClass;

/**
 * Form controller for Export edit forms.
 *
 * @ingroup effective_activism
 */
class ExportForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Organization $organization = NULL, Export $export = NULL) {
    $form = parent::buildForm($form, $form_state);
    $form['#theme'] = (new ReflectionClass($this))->getShortName();
    $entity = $this->entity;
    // Set values from path.
    $form['organization']['widget'][0]['target_id']['#default_value'] = Drupal::request()->get('organization');
    // Remove filter options that do not belong to selected organization.
    if (!empty($form['organization']['widget'][0]['target_id']['#default_value'])) {
      $allowed_filter_ids = OrganizationHelper::getFilters($organization, 0, 0, FALSE);
      if (!empty($form['filter']['widget'][0]['target_id']['#options'])) {
        foreach ($form['filter']['widget'][0]['target_id']['#options'] as $filter_id => $filter_name) {
          if (!in_array($filter_id, $allowed_filter_ids)) {
            unset($form['filter']['widget'][0]['target_id']['#options'][$filter_id]);
          }
        }
      }
    }
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $filter_id = $form_state->getValue('filter')[0]['target_id'];
    $allowed_filter_ids = OrganizationHelper::getFilters(Drupal::request()->get('organization'), 0, 0, FALSE);
    if (!in_array($filter_id, $allowed_filter_ids)) {
      $form_state->setErrorByName('filter', $this->t('<em>@organization</em> does not allow the filter <em>@filter</em>. Please select another organization or filter.', [
        '@organization' => Drupal::request()->get('organization')->label(),
        '@filter' => Filter::load($filter_id)->label(),
      ]));
    }
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
    $form_state->setRedirect('entity.export.canonical', [
      'organization' => PathHelper::transliterate(Drupal::request()->get('organization')->label()),
      'export' => $entity->id()
    ]);
  }

  /**
   * Returns the form array when using AJAX.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form array.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

}
