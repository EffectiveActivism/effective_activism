<?php

namespace Drupal\effective_activism\Helper;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\effective_activism\Constant;
use Symfony\Component\Routing\Route;

/**
 * Parameter conversion class for the effective_activism module.
 */
class ParamConverter implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    switch ($definition['type']) {
      case 'invitation':
        if (!empty($value) && is_numeric($value)) {
          $results = InvitationHelper::getInvitation($value);
          if (!empty($results)) {
            return $results[0];
          }
        }
        break;

      case Constant::ENTITY_ORGANIZATION:
        if (!empty($value) && is_string($value)) {
          return PathHelper::loadOrganizationBySlug($value);
        }
        break;

      case Constant::ENTITY_GROUP:
        if (!empty($value) && is_string($value)) {
          return PathHelper::loadGroupBySlug($value);
        }
        break;

      case Constant::ENTITY_RESULT_TYPE:
        if (!empty($value) && is_string($value)) {
          return PathHelper::loadResultTypeBySlug($value);
        }
        break;
     }
     return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && in_array($definition['type'], [
      'invitation',
      Constant::ENTITY_ORGANIZATION,
      Constant::ENTITY_GROUP,
      Constant::ENTITY_RESULT_TYPE,
    ]));
  }

}
