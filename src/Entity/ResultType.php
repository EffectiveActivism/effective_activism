<?php

namespace Drupal\effective_activism\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the Result type entity.
 *
 * @ConfigEntityType(
 *   id = "result_type",
 *   label = @Translation("Result type"),
 *   handlers = {
 *     "list_builder" = "Drupal\effective_activism\ListBuilder\ResultTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\effective_activism\Form\ResultTypeForm",
 *       "edit" = "Drupal\effective_activism\Form\ResultTypeForm",
 *     },
 *     "access" = "Drupal\effective_activism\AccessControlHandler\ResultTypeAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\effective_activism\RouteProvider\ResultTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "result_type",
 *   bundle_of = "result",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/o/{organization}/result-types/add",
 *     "collection" = "/o/{organization}/result-types",
 *     "edit-form" = "/o/{organization}/result-types/{result_type}/edit",
 *     "publish-form" = "/o/{organization}/result-types/{result_type}/publish",
 *   },
 * )
 */
class ResultType extends ConfigEntityBundleBase implements ResultTypeInterface {

  /**
   * The Result type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Result type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Result type name formatted as a machine name for use with importing.
   *
   * @var string
   */
  protected $importname;

  /**
   * The Result type description.
   *
   * @var string
   */
  public $description;

  /**
   * The Result type data types.
   *
   * @var array
   */
  public $datatypes;

  /**
   * The Result type organization.
   *
   * @var int
   */
  public $organization;

  /**
   * The Result type allowed groups.
   *
   * @var array
   */
  public $groups;

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    $first_sort = strnatcmp($b->organization, $a->organization);
    // If comparison matches organization, sort on labels.
    if ($first_sort === 0) {
      return strnatcasecmp($a->label, $b->label);
    }
    return $first_sort;
  }

}
