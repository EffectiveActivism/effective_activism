<?php

namespace Drupal\effective_activism\Helper;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\effective_activism\Helper\InvitationHelper;
use Symfony\Component\Routing\Route;

class ParamConverter implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    if (!empty($value) && is_numeric($value)) {
      $results = InvitationHelper::getInvitation($value);
      if (!empty($results)) {
        return $results[0];
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] === 'invitation');
  }

}
