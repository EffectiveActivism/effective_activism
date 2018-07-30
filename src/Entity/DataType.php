<?php

namespace Drupal\effective_activism\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Data type entity.
 *
 * @ConfigEntityType(
 *   id = "data_type",
 *   label = @Translation("Data type"),
 *   handlers = {
 *     "access" = "Drupal\effective_activism\AccessControlHandler\DataTypeAccessControlHandler",
 *   },
 *   config_prefix = "data_type",
 *   bundle_of = "data",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 * )
 */
class DataType extends ConfigEntityBundleBase implements DataTypeInterface {

  /**
   * The Data type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Data type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Data type description.
   *
   * @var string
   */
  public $description;

}
