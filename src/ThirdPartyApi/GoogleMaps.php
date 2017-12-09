<?php

namespace Drupal\effective_activism\ThirdPartyApi;

use Drupal;
use Drupal\Component\Utility\UrlHelper;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Entity\ThirdPartyContent;
use GuzzleHttp\Exception\ClientException;

/**
 * Implements a wrapper class for the Google Maps API.
 */
class GoogleMaps extends ThirdPartyApi {

  const GEOCODE_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

  // The maximum usage per day.
  const API_MAX = 2500;

  // A warning threshold for usage.
  const API_THRESHOLD = 1800;

  /**
   * Google Maps API key.
   *
   * @var string
   */
  private $key;

  /**
   * Day timestamp.
   *
   * @var int
   */
  private $timestamp;

  /**
   * Usage count.
   *
   * @var int
   */
  private $calls;

  /**
   * Latitude.
   *
   * @var float
   */
  private $latitude;

  /**
   * Longitude.
   *
   * @var float
   */
  private $longitude;

  /**
   * {@inheritdoc}
   */
  public function __construct(ThirdPartyContent $third_party_content) {
    parent::__construct($third_party_content);
    if ($this->thirdpartycontent->getType() !== Constant::THIRD_PARTY_CONTENT_TYPE_EXTENDED_LOCATION_INFORMATION) {
      throw new GoogleMapsException('Wrong third-party content type.');
    }
    $this->key = Drupal::config('effective_activism.settings')->get('google_maps_api_key');
    $this->timestamp = Drupal::config('effective_activism.settings')->get('google_maps_api_timestamp');
    $this->calls = Drupal::config('effective_activism.settings')->get('google_maps_api_usage');
    $this->thirdpartycontent = $third_party_content;
    $this->latitude = $third_party_content->get('field_latitude')->value;
    $this->longitude = $third_party_content->get('field_longitude')->value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function request() {
    // Proceed only if the required data is available.
    if (
      !empty($this->key) &&
      !empty($this->latitude) &&
      !empty($this->longitude)
    ) {
      try {
        // Set API usage.
        $settings = Drupal::configFactory()->getEditable('effective_activism.settings');
        // Reset usage tracking if a day has passed since last reset.
        if (empty($this->timestamp) || $this->timestamp + 86400 < time()) {
          $this->timestamp = time();
          $this->calls = 0;
          $settings->set('google_maps_api_timestamp', $this->timestamp);
        }
        $this->calls++;
        $settings->set('google_maps_api_usage', $this->calls);
        $settings->save(TRUE);
        $query = [
          'latlng' => sprintf('%s,%s', $this->latitude, $this->longitude),
          'key' => $this->key,
        ];
        $request = Drupal::httpClient()->get(sprintf('%s?%s', self::GEOCODE_URL, UrlHelper::buildQuery($query)));
        $response = $request->getBody()->getContents();
        if (!empty($response)) {
          $data = json_decode($response);
          if (
            json_last_error() === JSON_ERROR_NONE &&
            !empty($data->status) &&
            $data->status === 'OK' &&
            !empty($data->results) &&
            isset($data->results[0]->address_components)
          ) {
            // Extract address components for first hit.
            $address_components = $data->results[0]->address_components;
            foreach ($address_components as $component) {
              if (
                isset($component->types) &&
                isset($component->long_name)
              ) {
                switch ($component->types) {
                  case in_array('country', $component->types):
                    $this->thirdpartycontent->field_address_country = $component->long_name;
                    break;

                  case in_array('postal_code', $component->types):
                    $this->thirdpartycontent->field_address_postal_code = $component->long_name;
                    break;

                  case in_array('administrative_area_level_1', $component->types):
                    $this->thirdpartycontent->field_address_area_level_1 = $component->long_name;
                    break;

                  case in_array('administrative_area_level_2', $component->types):
                    $this->thirdpartycontent->field_address_area_level_2 = $component->long_name;
                    break;

                  case in_array('administrative_area_level_3', $component->types):
                    $this->thirdpartycontent->field_address_area_level_3 = $component->long_name;
                    break;

                  case in_array('locality', $component->types):
                    $this->thirdpartycontent->field_address_locality = $component->long_name;
                    break;

                  case in_array('sublocality', $component->types):
                    $this->thirdpartycontent->field_address_sublocality = $component->long_name;
                    break;

                  case in_array('neighborhood', $component->types):
                    $this->thirdpartycontent->field_address_neighborhood = $component->long_name;
                    break;

                  case in_array('route', $component->types):
                    $this->thirdpartycontent->field_address_street = $component->long_name;
                    break;

                }
              }
            }
          }
          else {
            throw new GoogleMapsException(sprintf('Unexpected format on JSON string: %s', substr($response, 0, 1000)));
          }
        }
        else {
          throw new GoogleMapsException('Empty JSON string.');
        }
      }
      catch (BadResponseException $exception) {
        throw new GoogleMapsException($exception->getMessage());
      }
      catch (RequestException $exception) {
        throw new GoogleMapsException($exception->getMessage());
      }
      catch (ClientException $exception) {
        throw new GoogleMapsException($exception->getMessage());
      }
    }
    // Save third-party content entity.
    parent::request();
  }

  /**
   * {@inheritdoc}
   */
  public static function status() {
    $credits = 0;
    $timestamp = Drupal::config('effective_activism.settings')->get('google_maps_api_timestamp');
    $calls = Drupal::config('effective_activism.settings')->get('google_maps_api_usage');
    // Reset usage tracking if a day has passed since last reset.
    if (empty($timestamp) || $timestamp + 86400 < time()) {
      $settings = Drupal::configFactory()->getEditable('effective_activism.settings');
      $settings->set('google_maps_api_timestamp', time());
      $settings->set('google_maps_api_usage', 0);
      $settings->save(TRUE);
    }
    if ($calls < self::API_THRESHOLD) {
      $status = REQUIREMENT_OK;
      $description = sprintf('You have made %d calls out of %d for today.', $calls, self::API_MAX);
    }
    elseif ($calls >= self::API_MAX) {
      $status = REQUIREMENT_ERROR;
      $description = sprintf('You have no API calls left for today.');
    }
    elseif ($calls >= self::API_THRESHOLD) {
      $status = REQUIREMENT_WARNING;
      $description = sprintf('You have less than %d calls left for today. %d out of %d remain.', self::API_MAX - self::API_THRESHOLD, $calls, self::API_MAX);
    }
    return [
      'title' => 'Third-party content API Google Maps',
      'value' => $calls,
      'description' => $description,
      'severity' => $status,
    ];
  }

}
