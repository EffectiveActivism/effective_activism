<?php

namespace Drupal\effective_activism\Plugin\Field\FieldWidget;

use Drupal\effective_activism\Helper\AccountHelper;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Allows for selection of organizations belonging to user.
 *
 * @FieldWidget(
 *   id = "organization_selector",
 *   label = @Translation("Organization widget"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class OrganizationWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $organization = $form_state->getFormObject()->getEntity();
    $current_id = !empty($organization) ? $organization->id() : FALSE;
    $allowed_organizations = AccountHelper::getManagedOrganizations();
    $options = [];
    foreach ($allowed_organizations as $oid => $organization) {
      $options[$oid] = $organization->label();
    }
    // Force a default value if possible.
    $default_value = NULL;
    if (!empty($items[$delta]->target_id)) {
      $default_value = $items[$delta]->target_id;
    }
    elseif (!empty($options)) {
      $keys = array_keys($options);
      $default_value = reset($keys);
    }
    $element['target_id'] = [
      '#title' => $this->t('Organization'),
      '#type' => 'radios',
      '#default_value' => $default_value,
      '#options' => $options,
      '#disabled' => $current_id ? TRUE : FALSE,
    ];
    return $element;
  }

}
