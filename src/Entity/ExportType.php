<?php

namespace Drupal\effective_activism\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Export type entity.
 *
 * @ConfigEntityType(
 *   id = "export_type",
 *   label = @Translation("Export type"),
 *   config_prefix = "export_type",
 *   bundle_of = "export",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 * )
 */
class ExportType extends ConfigEntityBundleBase implements ExportTypeInterface {

  /**
   * The Export type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Export type label.
   *
   * @var string
   */
  protected $label;

}
