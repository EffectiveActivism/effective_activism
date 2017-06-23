<?php

namespace Drupal\effective_activism\Helper;

use Drupal;
use Drupal\effective_activism\Constant;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Functions for storing and retrieving locations from the Google Maps API.
 */
class LocationHelper {

  const AUTOCOMPLETE_URL = 'https://maps.googleapis.com/maps/api/place/autocomplete/json';
  const GEOCODE_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

  /**
   * Validates an address.
   *
   * @param string $address
   *   An address string that needs to be validated against Google Maps API
   *   format.
   *
   * @return bool
   *   Returns TRUE if string is a valid Google Maps address, FALSE if not.
   *   If any connection errors occur, validation returns TRUE.
   */
  public static function validateAddress($address) {
    if (empty($address)) {
      return TRUE;
    }
    $match = FALSE;
    // First check cache.
    $database = Drupal::database();
    $results = $database->select(Constant::LOCATION_CACHE_TABLE, 'location')
      ->fields('location', ['id'])
      ->condition('location.address', $address)
      ->countQuery()
      ->execute()
      ->fetchField();
    if ($results > 0) {
      $match = TRUE;
    }
    else {
      $valid_addresses = self::getAddressSuggestions($address);
      if ($valid_addresses !== FALSE && in_array($address, $valid_addresses)) {
        // Get coordinates and store valid address in cache.
        $coordinates = self::getCoordinates($address);
        $database->insert(Constant::LOCATION_CACHE_TABLE)->fields([
          'address' => $address,
          'lat' => empty($coordinates['lat']) ? NULL : $coordinates['lat'],
          'lon' => empty($coordinates['lon']) ? NULL : $coordinates['lon'],
        ])->execute();
        $match = TRUE;
      }
    }
    return $match;
  }

  /**
   * Get locations based on input.
   *
   * @param string $input
   *   A location string.
   *
   * @return array
   *   Returns array of suggestions or FALSE if any connection errors occur.
   */
  public static function getAddressSuggestions($input) {
    $suggestions = [];
    if (!empty($input)) {
      $query = [
        'input' => $input,
        'language' => Drupal::languageManager()->getCurrentLanguage()->getName(),
      ];
      $json = self::request(self::AUTOCOMPLETE_URL, $query);
      if (isset($json->status) && $json->status === 'OK' && !empty($json->predictions)) {
        foreach ($json->predictions as $prediction) {
          $suggestions[] = $prediction->description;
        }
      }
    }
    return $suggestions;
  }

  /**
   * Get coordinates based off an address.
   *
   * @param string $address
   *   A Google address string.
   *
   * @return array
   *   Returns array of coordinates as array('lat' => float, 'lon' => float)
   *   or FALSE if no coordinates are found.
   */
  public static function getCoordinates($address) {
    $coordinates = [
      'lat' => '',
      'lon' => '',
    ];
    if (!empty($address)) {
      // First check cache.
      $database = Drupal::database();
      $result = $database->select(Constant::LOCATION_CACHE_TABLE, 'location')
        ->fields('location', [
          'lat',
          'lon',
        ])
        ->condition('location.address', $address)
        ->execute();
      $location = $result->fetch();
      if ($location !== FALSE) {
        $coordinates['lat'] = $location->lat;
        $coordinates['lon'] = $location->lon;
      }
      else {
        // If not stored locally, try to retrieve from geocoding service.
        $query = [
          'address' => $address,
        ];
        $json = self::request(self::GEOCODE_URL, $query);
        if (
          !empty($json->status) &&
          $json->status === 'OK' &&
          !empty($json->results[0]->geometry->location->lat) &&
          !empty($json->results[0]->geometry->location->lng)
        ) {
          $coordinates['lat'] = $json->results[0]->geometry->location->lat;
          $coordinates['lon'] = $json->results[0]->geometry->location->lng;
        }
      }
    }
    return $coordinates;
  }

  /**
   * Make a request to Google APIs.
   *
   * @param string $url
   *   A Google API url.
   * @param array $query
   *   The query to submit.
   *
   * @return string
   *   Returns JSON response.
   */
  private static function request($url, array $query) {
    // Set Google API key.
    $key = Drupal::config('effective_activism.settings')->get('google_maps_api_key');
    if (!empty($key)) {
      $query['key'] = $key;
      $built_query = UrlHelper::buildQuery($query);
      try {
        $request = Drupal::httpClient()->get(sprintf('%s?%s', $url, $built_query));
        $response = $request->getBody()->getContents();
        if (!empty($response)) {
          return json_decode($response);
        }
      }
      catch (BadResponseException $exception) {
        return FALSE;
      }
      catch (RequestException $exception) {
        return FALSE;
      }
    }
    return FALSE;
  }

}
