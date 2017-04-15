<?php

namespace Drupal\effective_activism\Controller\Misc;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller class for contact information.
 */
class ContactInformationController extends ControllerBase {

  const THEME_ID = 'contact_information';

  /**
   * @var array
   *   The entity types supported by this controller.
   */
  private $allowed_entity_types = [
    'organization',
    'group',
  ];

  /**
   * var array
   *   The contact information field names.
   */
  private $field_names = [
    'website',
    'phone_number',
    'email_address',
    'location',
  ];

  /**
   * Returns an array of fields for contact information.
   *
   * @param EntityInterface $entity
   *   The entity to provide contact information for.
   *
   * @return array
   *   A render array.
   */
  public function view(EntityInterface $entity) {
    $content['#theme'] = self::THEME_ID;
    // Do not process disallowed entities.
    if (in_array($entity->getEntityTypeId(), $this->allowed_entity_types)) {
      foreach ($this->field_names as $field_name) {
        $content['fields'][$field_name] = $entity->get($field_name);
      }
    }
    return $content;
  }

  /**
   * Returns an array of fields for contact information.
   *
   * @param array $form
   *   An optional form array.
   *
   * @return array
   *   A render array.
   */
  public function form(array $form) {
    $content['#theme'] = self::THEME_ID;
    if (!empty($form['#id']) && !in_array($form['#id'], array_map(function($entity) {
      return sprintf('%s_edit_form', $entity);
    }, $this->allowed_entity_types))) {
      foreach ($this->field_names as $field_name) {
        $content['fields'][$field_name] = $form[$field_name];
      }
    }
    return $content;
  }
}
