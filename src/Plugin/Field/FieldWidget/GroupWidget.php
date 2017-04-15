<?php

namespace Drupal\effective_activism\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Helper\AccountHelper;

/**
 * Plugin implementation of the group widget.
 *
 * @FieldWidget(
 *   id = "group_selector",
 *   label = @Translation("Parent group widget"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class GroupWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $allowed_groups = AccountHelper::getGroups();
    $options = [];
    foreach ($allowed_groups as $gid => $group) {
      $options[$gid] = sprintf('%s (%s)', $group->label(), $group->organization->entity->label());
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
    $element['target_id'] = array(
      '#title' => $this->t('Group'),
      '#type' => 'radios',
      '#default_value' => $default_value,
      '#options' => $options,
    );
    return $element;
  }

}
