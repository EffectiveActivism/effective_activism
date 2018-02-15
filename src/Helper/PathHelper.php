<?php

namespace Drupal\effective_activism\Helper;

use Drupal;
use Drupal\Core\Routing\RouteMatchInterface\RouteMatchInterface;
use Drupal\effective_activism\Entity\Organization;
use Drupal\effective_activism\Entity\Group;

/**
 * Helper functions for translating slugs.
 */
class PathHelper {

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
      $slug = self::transliterate($organization->label());
      if ($slug === $title_as_slug) {
        return $organization;
      }
    }
    return NULL;
  }

  /**
   * Load group by title.
   *
   * @param string $title_as_slug
   *   The organization title in slug form to load.
   *
   * @return \Drupal\effective_activism\Entity\Organization|NULL
   *   An organization entity or NULL if not found.
   */
  public static function loadGroupBySlug($title_as_slug) {
    foreach (Group::loadMultiple(Drupal::entityQuery('group')->execute()) as $group) {
      $slug = self::transliterate($group->label());
      if ($slug === $title_as_slug) {
        return $group;
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
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $slug = strtolower($slug);
    $slug = str_replace(" ", "-", $slug);
    $slug = preg_replace("/[^a-z0-9\-]/", '', $slug);
    $slug = substr($slug, 0, 30);
    return $slug;
  }

}
