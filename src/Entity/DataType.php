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
 *     "list_builder" = "Drupal\effective_activism\Helper\ListBuilder\DataTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\effective_activism\Helper\RouteProvider\DataTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "data_type",
 *   bundle_of = "data",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/manage/data-types/{data_type}",
 *     "add-form" = "/manage/data-types/add",
 *     "edit-form" = "/manage/data-types/{data_type}/edit",
 *     "collection" = "/manage/data-types"
 *   }
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
