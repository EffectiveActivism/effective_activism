<?php

namespace Drupal\effective_activism\Helper;

use Drupal;
use Drupal\Core\Routing\RouteMatchInterface\RouteMatchInterface;
use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Entity\EventTemplate;
use Drupal\effective_activism\Entity\Export;
use Drupal\effective_activism\Entity\Filter;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Import;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Entity\ResultType;

/**
 * Helper functions for translating slugs.
 */
class PathHelper {

  /**
   * Load event by id.
   *
   * @param string $id
   *   The event id to load.
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization to search within.
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization to search within.
   *
   * @return \Drupal\effective_activism\Entity\Event|NULL
   *   An event entity or NULL if not found.
   */
  public static function loadEventById($id, Organization $organization, Group $group) {
    $event = Event::load($id);
    if (
      !empty($event) &&
      $event->parent->entity->id() === $group->id() &&
      $event->parent->entity->organization->entity->id() === $organization->id()
    ) {
      return $event;
    }
    return NULL;
  }

  /**
   * Load event template by id.
   *
   * @param string $id
   *   The event template id to load.
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization to search within.
   *
   * @return \Drupal\effective_activism\Entity\EventTemplate|NULL
   *   An event template entity or NULL if not found.
   */
  public static function loadEventTemplateById($id, Organization $organization) {
    $event_template = EventTemplate::load($id);
    if (
      !empty($event_template) &&
      $event_template->organization->entity->id() === $organization->id()
    ) {
      return $event_template;
    }
    return NULL;
  }

  /**
   * Load export by id.
   *
   * @param string $id
   *   The export id to load.
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization to search within.
   *
   * @return \Drupal\effective_activism\Entity\Export|NULL
   *   An export entity or NULL if not found.
   */
  public static function loadExportById($id, Organization $organization) {
    $export = Export::load($id);
    if (
      !empty($export) &&
      $export->organization->entity->id() === $organization->id()
    ) {
      return $export;
    }
    return NULL;
  }

  /**
   * Load filter by id.
   *
   * @param string $id
   *   The filter id to load.
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization to search within.
   *
   * @return \Drupal\effective_activism\Entity\Filter|NULL
   *   An filter entity or NULL if not found.
   */
  public static function loadFilterById($id, Organization $organization) {
    $filter = Filter::load($id);
    if (
      !empty($filter) &&
      $filter->organization->entity->id() === $organization->id()
    ) {
      return $filter;
    }
    return NULL;
  }

  /**
   * Load group by title.
   *
   * @param string $title_as_slug
   *   The group title in slug form to load.
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization to search within.
   *
   * @return \Drupal\effective_activism\Entity\Group|NULL
   *   A group entity or NULL if not found.
   */
  public static function loadGroupBySlug($title_as_slug, Organization $organization) {
    foreach (Group::loadMultiple(Drupal::entityQuery('group')
      ->condition('organization', $organization->id())
      ->execute()) as $group) {
      if (self::transliterate($group->label()) === $title_as_slug) {
        return $group;
      }
    }
    return NULL;
  }

  /**
   * Load import by id.
   *
   * @param string $id
   *   The import id to load.
   * @param \Drupal\effective_activism\Entity\Organization $organization
   *   The organization to search within.
   *
   * @return \Drupal\effective_activism\Entity\Import|NULL
   *   An import entity or NULL if not found.
   */
  public static function loadImportById($id, Organization $organization, Group $group) {
    $import = Import::load($id);
    if (
      !empty($import) &&
      $import->parent->entity->id() === $group->id() &&
      $import->parent->entity->organization->entity->id() === $organization->id()
    ) {
      return $import;
    }
    return NULL;
  }

  /**
   * Load organization by title.
   *
   * @param string $title_as_slug
   *   The organization title in slug form to load.
   *
   * @return \Drupal\effective_activism\Entity\Organization|NULL
   *   An organization entity or NULL if not found.
   */
  public static function loadOrganizationBySlug($title_as_slug) {
    foreach (OrganizationHelper::getOrganizations() as $organization) {
      if (self::transliterate($organization->label()) === $title_as_slug) {
        return $organization;
      }
    }
    return NULL;
  }

  /**
   * Load result type by import name.
   *
   * @param string $import_name_as_slug
   *   The result type import name in slug form to load.
   *
   * @return \Drupal\effective_activism\Entity\ResultType|NULL
   *   A result type configuration entity or NULL if not found.
   */
  public static function loadResultTypeBySlug($import_name_as_slug, Organization $organization) {
    foreach (ResultType::loadMultiple(Drupal::entityQuery('result_type')
      ->condition('organization', $organization->id())
      ->execute()) as $result_type) {
      if (self::transliterate($result_type->get('importname')) === $import_name_as_slug) {
        return $result_type;
      }
    }
    return NULL;
  }

  /**
   * Returns an ascii-friendly slug from a text string.
   *
   * @param string $text
   *   The text to transform into a path.
   *
   * @return string|bool
   *   Returns a slug based on the text.
   */
  public static function transliterate($text) {
    $slug = trim($text);
    $slug = preg_replace('/\s+/', ' ', $slug);
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
    $slug = strtolower($slug);
    $slug = str_replace(" ", "-", $slug);
    $slug = preg_replace("/[^a-z0-9\-]/", '', $slug);
    $slug = substr($slug, 0, 30);
    return $slug;
  }

}
