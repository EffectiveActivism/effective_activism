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
        if (!empty($value) && is_string($value)) {
          $results = InvitationHelper::getInvitation($value);
          if (!empty($results)) {
            return $results[0];
          }
        }
        break;

      case Constant::ENTITY_EVENT:
        if (!empty($value) && is_string($value)) {
          return PathHelper::loadEventById(
            $value,
            $defaults[Constant::ENTITY_ORGANIZATION],
            $defaults[Constant::ENTITY_GROUP]
          );
        }
        break;

      case Constant::ENTITY_EVENT_TEMPLATE:
        if (!empty($value) && is_string($value)) {
          return PathHelper::loadEventTemplateById(
            $value,
            $defaults[Constant::ENTITY_ORGANIZATION]
          );
        }
        break;

      case Constant::ENTITY_EXPORT:
        if (!empty($value) && is_string($value)) {
          return PathHelper::loadExportById(
            $value,
            $defaults[Constant::ENTITY_ORGANIZATION]
          );
        }
        break;

      case Constant::ENTITY_FILTER:
        if (!empty($value) && is_string($value)) {
          return PathHelper::loadFilterById(
            $value,
            $defaults[Constant::ENTITY_ORGANIZATION]
          );
        }
        break;

      case Constant::ENTITY_GROUP:
        if (!empty($value) && is_string($value)) {
          return PathHelper::loadGroupBySlug(
            $value,
            $defaults[Constant::ENTITY_ORGANIZATION]
          );
        }
        break;

      case Constant::ENTITY_IMPORT:
        if (!empty($value) && is_string($value)) {
          return PathHelper::loadImportById(
            $value,
            $defaults[Constant::ENTITY_ORGANIZATION],
            $defaults[Constant::ENTITY_GROUP]
          );
        }
        break;

      case Constant::ENTITY_ORGANIZATION:
        if (!empty($value) && is_string($value)) {
          return PathHelper::loadOrganizationBySlug($value);
        }
        break;

      case Constant::ENTITY_RESULT_TYPE:
        if (!empty($value) && is_string($value)) {
          return PathHelper::loadResultTypeBySlug(
            $value,
            $defaults[Constant::ENTITY_ORGANIZATION]
          );
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
      Constant::ENTITY_EVENT,
      Constant::ENTITY_EVENT_TEMPLATE,
      Constant::ENTITY_EXPORT,
      Constant::ENTITY_FILTER,
      Constant::ENTITY_GROUP,
      Constant::ENTITY_IMPORT,
      Constant::ENTITY_ORGANIZATION,
      Constant::ENTITY_RESULT_TYPE,
    ]));
  }

}
