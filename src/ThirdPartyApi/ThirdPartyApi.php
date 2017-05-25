<?php

namespace Drupal\effective_activism\ThirdPartyApi;

abstract class ThirdPartyApi {

  protected $third_party_content;

  public function __construct($third_party_content) {
    $this->third_party_content = $third_party_content;
  }

  /**
   * Perform a request to the third-party api.
   */
  public function request() {
    $this->third_party_content->setNewRevision();
    $this->third_party_content->save();
  }

}
