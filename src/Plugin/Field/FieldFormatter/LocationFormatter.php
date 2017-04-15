<?php

namespace Drupal\effective_activism\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'location' formatter.
 *
 * @FieldFormatter(
 *   id = "location_default",
 *   module = "effective_activism",
 *   label = @Translation("Location"),
 *   field_types = {
 *     "location"
 *   }
 * )
 */
class LocationFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $address = empty($item->address) ? '' : sprintf('<div class=\'address\'>%s</div>', $item->address);
      $extra_information = empty($item->extra_information) ? '' : sprintf('<div class=\'extra-information\'>%s</div>', $item->extra_information);
      $elements[$delta] = [
        '#markup' => $address . $extra_information,
      ];
    }
    return $elements;
  }

}
