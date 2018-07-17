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
 *     "access" = "Drupal\effective_activism\AccessControlHandler\ThirdPartyContentTypeAccessControlHandler",
 *   },
 *   config_prefix = "third_party_content_type",
 *   bundle_of = "third_party_content",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
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
