<?php

namespace Drupal\effective_activism\Form\Result;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Helper\AccountHelper;
use Drupal\effective_activism\Helper\OrganizationHelper;
use Drupal\effective_activism\Helper\ResultTypeHelper;

/**
 * Class ResultTypeForm.
 *
 * @package Drupal\effective_activism\Form
 */
class ResultTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $selected_organization = $this->entity->organization;
    $selected_groups = $this->entity->groups;
    $selected_datatypes = !empty($this->entity->datatypes) ? array_filter(array_values($this->entity->datatypes), function ($value) {
      return $value !== 0;
    }) : [];
    // Get available organizations.
    $available_organizations = array_reduce(AccountHelper::getManagedOrganizations(), function ($result, $organization) {
      $result[$organization->id()] = $organization->label();
      return $result;
    }, []);
    // Get available groups.
    $available_groups = !empty($selected_organization) ? array_reduce(OrganizationHelper::getGroups(Organization::load($selected_organization)), function ($result, $group) {
      $result[$group->id()] = $group->label();
      return $result;
    }, []) : [];
    // Get available data types.
    $data_bundles = \Drupal::entityManager()->getBundleInfo('data');
    $available_datatypes = [];
    foreach ($data_bundles as $bundle_name => $bundle_info) {
      $available_datatypes[$bundle_name] = $bundle_info['label'];
    }
    // Build form.
    $form['#prefix'] = '<div id="ajax">';
    $form['#suffix'] = '</div>';
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('Label for the Result type.'),
      '#attributes' => [
        'placeholder' => t('Label'),
      ],
      '#required' => TRUE,
    ];
    $form['importname'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Import name'),
      '#description' => $this->t('This name is used when importing results of this type. Can only contain lowercase letters, numbers, and underscores.'),
      '#default_value' => $this->entity->get('importname'),
      '#machine_name' => [
        'exists' => '\Drupal\effective_activism\Helper\ResultTypeHelper::checkTypedImportNameExists',
        'label' => $this->t('Import name'),
      ],
      '#attributes' => [
        'placeholder' => t('Import name'),
      ],
      '#required' => TRUE,
      // Disallow changing import name after result type has been created.
      '#disabled' => empty($this->entity->id()) ? FALSE : TRUE,
    ];
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->description,
      '#description' => $this->t('Description for the Result type.'),
      '#attributes' => [
        'placeholder' => t('Description'),
      ],
      '#required' => FALSE,
    ];
    $form['datatypes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Data types'),
      '#default_value' => empty($selected_datatypes) ? [] : $selected_datatypes,
      '#options' => $available_datatypes,
      '#description' => $this->t('Data types available for the Result type.'),
      '#required' => TRUE,
    ];
    $form['organization'] = [
      '#type' => 'select',
      '#title' => $this->t('Organization'),
      '#default_value' => $selected_organization,
      '#tags' => TRUE,
      '#description' => $this->t('The organization that the Result type is available for. Once this option is saved, it cannot be changed.'),
      '#options' => $available_organizations,
      '#required' => TRUE,
      '#disabled' => $this->entity->isNew() ? FALSE : TRUE,
      '#ajax' => [
        'callback' => [$this, 'updateAvailableGroups'],
        'wrapper' => 'ajax',
      ],
    ];
    $form['groups'] = [
      '#type' => 'select',
      '#title' => $this->t('Groups'),
      '#default_value' => $selected_groups,
      '#description' => $this->t('The groups the Result type is available for.'),
      '#options' => $available_groups,
      '#multiple' => TRUE,
      '#required' => FALSE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $import_name = $form_state->getValue('importname');
    $organization_id = $form_state->getValue('organization');
    // Verify that import name is unique within organization.
    // Only perform this check for new result types.
    if (!empty($import_name) && !empty($organization_id) && empty($this->entity->id()) && !ResultTypeHelper::isUniqueImportName($import_name, $organization_id)) {
      $form_state->setErrorByName('import_name', $this->t('This import name is already in use for your organization. Please type in another one.'));
    }
    // Derive entity id from import name.
    if (!empty($import_name) && empty($this->entity->id())) {
      $form_state->setValue('id', ResultTypeHelper::getUniqueID($import_name));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->entity->save();
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Result type.', [
          '%label' => $this->entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Result type.', [
          '%label' => $this->entity->label(),
        ]));
    }
    // Update fields for this entity type.
    ResultTypeHelper::updateBundleSettings($this->entity);
    // Add a tagging field to the result type.
    ResultTypeHelper::addTaxonomyField($this->entity);
    $form_state->setRedirectUrl($this->entity->urlInfo('collection'));
  }

  /**
   * Populates the groups #options element.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form array.
   */
  public function updateAvailableGroups(array &$form, FormStateInterface $form_state) {
    return $form;
  }

}
