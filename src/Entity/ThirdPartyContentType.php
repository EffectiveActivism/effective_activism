<?php

namespace Drupal\effective_activism\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the ThirdPartyContent entity.
 *
 * @ConfigEntityType(
 *   id = "third_party_content_type",
 *   label = @Translation("Third-party content type"),
 *   handlers = {
 *     "list_builder" = "Drupal\effective_activism\ListBuilder\ThirdPartyContentTypeListBuilder",
 *     "access" = "Drupal\effective_activism\AccessControlHandler\ThirdPartyContentTypeAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\effective_activism\RouteProvider\ThirdPartyContentTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "third_party_content_type",
 *   bundle_of = "third_party_content",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/manage/third-party-content/{third_party_content_type}",
 *     "add-form" = "/manage/third-party-content/add",
 *     "edit-form" = "/manage/third-party-content/{third_party_content_type}/edit",
 *     "collection" = "/manage/third-party-content"
 *   }
 * )
 */
class ThirdPartyContentType extends ConfigEntityBundleBase implements ThirdPartyContentTypeInterface {

  /**
   * The ThirdPartyContentType ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The ThirdPartyContentType label.
   *
   * @var string
   */
  protected $label;

  /**
   * The ThirdPartyContentType description.
   *
   * @var string
   */
  public $description;

}
