<?php

namespace Drupal\effective_activism\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Helper\AccountHelper;

/**
 * Allows for selection of filters belonging to organization.
 *
 * @FieldWidget(
 *   id = "filter_selector",
 *   label = @Translation("Filter widget"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class FilterWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $filter = $form_state->getFormObject()->getEntity();
    $current_id = !empty($filter) ? $filter->id() : FALSE;
    $allowed_filters = AccountHelper::getFilters();
    $options = [];
    foreach ($allowed_filters as $filter_id => $filter) {
      $options[$filter_id] = sprintf('%s (%s)', $filter->label(), $filter->organization->entity->label());
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
      '#title' => $this->t('Filter'),
      '#type' => 'select',
      '#default_value' => $default_value,
      '#options' => $options,
      '#disabled' => $current_id ? TRUE : FALSE,
    ];
    return $element;
  }

}
