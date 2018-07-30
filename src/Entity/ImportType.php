<?php

namespace Drupal\effective_activism\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Import type entity.
 *
 * @ConfigEntityType(
 *   id = "import_type",
 *   label = @Translation("Import type"),
 *   handlers = {
 *     "access" = "Drupal\effective_activism\AccessControlHandler\ImportTypeAccessControlHandler",
 *   },
 *   config_prefix = "import_type",
 *   bundle_of = "import",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 * )
 */
class ImportType extends ConfigEntityBundleBase implements ImportTypeInterface {

  /**
   * The Import type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Import type label.
   *
   * @var string
   */
  protected $label;

}
