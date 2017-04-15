<?php

namespace Drupal\effective_activism\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\effective_activism\Constant;

/**
 * Plugin implementation of the 'location' field type.
 *
 * @FieldType(
 *   id = "location",
 *   label = @Translation("Location"),
 *   description = @Translation("Stores a human-readable string and coordinates of a location."),
 *   category = @Translation("Effective Activism"),
 *   default_widget = "location_default",
 *   default_formatter = "location_default"
 * )
 */
class LocationType extends FieldItemBase implements FieldItemInterface {

  const ADDRESS_MAXLENGTH = 256;

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['address'] = DataDefinition::create('string')
      ->setLabel(t('Address'));
    $properties['extra_information'] = DataDefinition::create('string')
      ->setLabel(t('Extra information'));
    $properties['latitude'] = DataDefinition::create('float')
      ->setLabel(t('Latitude'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\effective_activism\Helper\LocationCoordinates\Latitude');
    $properties['longitude'] = DataDefinition::create('float')
      ->setLabel(t('Longitude'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\effective_activism\Helper\LocationCoordinates\Longitude');
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'address' => [
          'description' => 'A Google-compatible address.',
          'type' => 'varchar',
          'length' => self::ADDRESS_MAXLENGTH,
        ],
        'extra_information' => [
          'description' => 'Extra address information.',
          'type' => 'varchar',
          'length' => self::ADDRESS_MAXLENGTH,
        ],
        'latitude' => [
          'description' => 'The latitude of the address GPS position.',
          'type' => 'float',
          'size' => 'big',
        ],
        'longitude' => [
          'description' => 'The longitude of the address GPS position.',
          'type' => 'float',
          'size' => 'big',
        ],
      ],
      'indexes' => [
        'address' => ['address'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $address = $this->get('address')->getValue();
    $extra_information = $this->get('extra_information')->getValue();
    return empty($address) && empty($extra_information);
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = parent::getConstraints();
    $constraints[] = $constraint_manager->create('ComplexData', [
      'address' => [
        'Length' => [
          'max' => self::ADDRESS_MAXLENGTH,
          'maxMessage' => t('%name: the location address may not be longer than @max characters.', [
            '%name' => $this->getFieldDefinition()->getLabel(),
            '@max' => self::ADDRESS_MAXLENGTH,
          ]),
        ],
      ],
      'extra_information' => [
        'Length' => [
          'max' => self::ADDRESS_MAXLENGTH,
          'maxMessage' => t('%name: the location extra information may not be longer than @max characters.', [
            '%name' => $this->getFieldDefinition()->getLabel(),
            '@max' => self::ADDRESS_MAXLENGTH,
          ]),
        ],
      ],
    ]);
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    $values['latitude'] = NULL;
    $values['longitude'] = NULL;
    if (!empty($values['address'])) {
      // Retrieve the GPS coordinates from the cached locations table.
      $location = \Drupal::database()
        ->select(Constant::LOCATION_CACHE_TABLE, 'location')
        ->fields('location', [
          'lat',
          'lon',
        ])
        ->condition('address', $values['address'])
        ->execute()
        ->fetchAssoc();
      $values['latitude'] = $location['lat'];
      $values['longitude'] = $location['lon'];
    }
    parent::setValue($values, $notify);
  }
}
