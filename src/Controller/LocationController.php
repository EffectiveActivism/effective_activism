<?php

namespace Drupal\effective_activism\Controller;

use Drupal\effective_activism\Helper\LocationHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller class for locations.
 */
class LocationController {

  /**
   * Returns response for the location autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions for countries.
   */
  public function autocomplete(Request $request) {
    $autocomplete_suggestions = [];
    $string = $request->query->get('q');
    $suggestions = LocationHelper::getAddressSuggestions($string);
    foreach ($suggestions as $suggestion) {
      $autocomplete_suggestions[] = ['value' => $suggestion, 'label' => $suggestion];
    }
    return new JsonResponse($autocomplete_suggestions);
  }

}
