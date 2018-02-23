<?php

namespace Drupal\effective_activism\Form;

use Drupal;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\effective_activism\Entity\DataType;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Helper\OrganizationHelper;
use Drupal\effective_activism\Helper\ResultTypeHelper;
use Drupal\effective_activism\Helper\PathHelper;
use ReflectionClass;

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
    // Get available data types.
    $data_bundles = Drupal::entityManager()->getBundleInfo('data');
    $available_datatypes = [];
    foreach ($data_bundles as $bundle_name => $bundle_info) {
      $data_type = DataType::load($bundle_name);
      $available_datatypes[$bundle_name] = sprintf('%s<br><small><em>%s</em></small>', $bundle_info['label'], $data_type->description);
    }
    // Build form.
    $form['#prefix'] = '<div id="ajax">';
    $form['#suffix'] = '</div>';
    $form['#theme'] = (new ReflectionClass($this))->getShortName();
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
      '#default_value' => !empty($this->entity->datatypes) ? array_filter(
        array_values($this->entity->datatypes), function ($value) {
          return $value !== 0;
        }) : [],
      '#options' => $available_datatypes,
      '#description' => $this->t('Data types available for the Result type.'),
      '#required' => TRUE,
    ];
    $form['groups'] = [
      '#type' => 'select',
      '#title' => $this->t('Groups'),
      '#default_value' => $this->entity->groups,
      '#description' => $this->t('The groups the Result type is available for.'),
      '#options' => array_reduce(OrganizationHelper::getGroups(Drupal::request()->get('organization')), function ($result, $group) {
          $result[$group->id()] = $group->label();
          return $result;
        }, []),
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
    // Verify that import name is unique within organization.
    // Only perform this check for new result types.
    if (
      !empty($import_name) &&
      empty($this->entity->id()) &&
      !ResultTypeHelper::isUniqueImportName($import_name, Drupal::request()->get('organization')->id())
    ) {
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
    $form_state->setRedirectUrl(new Url('entity.organization.result_types', [
      'organization' => PathHelper::transliterate(Organization::load($this->entity->get('organization'))->label()),
    ]));
  }

}
