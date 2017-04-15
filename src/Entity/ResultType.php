<?php

namespace Drupal\effective_activism\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Result type entity.
 *
 * @ConfigEntityType(
 *   id = "result_type",
 *   label = @Translation("Result type"),
 *   handlers = {
 *     "list_builder" = "Drupal\effective_activism\Helper\ListBuilder\ResultTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\effective_activism\Form\Result\ResultTypeForm",
 *       "edit" = "Drupal\effective_activism\Form\Result\ResultTypeForm",
 *     },
 *     "access" = "Drupal\effective_activism\Helper\AccessControlHandler\ResultTypeAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\effective_activism\Helper\RouteProvider\ResultTypeHtmlRouteProvider",
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
 *     "canonical" = "/manage/result-types/{result_type}",
 *     "add-form" = "/manage/result-types/add",
 *     "edit-form" = "/manage/result-types/{result_type}/edit",
 *     "publish-form" = "/manage/result-types/{result_type}/publish",
 *     "collection" = "/manage/result-types",
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

}
