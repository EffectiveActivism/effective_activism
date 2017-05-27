<?php

namespace Drupal\effective_activism\ThirdPartyApi;

use Drupal\effective_activism\Entity\ThirdPartyContent;

/**
 * Provides an abstract wrapper class for third-party apis.
 */
abstract class ThirdPartyApi {

  protected $thirdpartycontent;

  /**
   * Constructs a third-party api wrapper.
   *
   * @param \Drupal\effective_activism\Entity\ThirdPartyContent $third_party_content
   *   The third-party content to populate with data.
   */
  public function __construct(ThirdPartyContent $third_party_content) {
    $this->thirdpartycontent = $third_party_content;
  }

  /**
   * Perform a request to the third-party api.
   */
  public function request() {
    $this->thirdpartycontent->setNewRevision();
    $this->thirdpartycontent->save();
  }

}
