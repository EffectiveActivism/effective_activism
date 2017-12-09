<?php

namespace Drupal\effective_activism\ThirdPartyApi;

use Drupal;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Entity\ThirdPartyContent;
use GuzzleHttp\Exception\ClientException;

/**
 * Implements a wrapper class for the DarkSky weather API.
 */
class DarkSky extends ThirdPartyApi {

  const API_URL = 'https://api.darksky.net/forecast';

  // The units to use for measurements.
  const UNITS = 'si';

  // The maximum number of API calls per day.
  const API_MAX = 1000;

  // A warning threshold for API calls.
  const API_THRESHOLD = 800;

  /**
   * Dark Sky API key.
   *
   * @var string
   */
  private $key;

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
   * Timestamp.
   *
   * @var int
   */
  private $time;

  /**
   * {@inheritdoc}
   */
  public function __construct(ThirdPartyContent $third_party_content) {
    parent::__construct($third_party_content);
    if ($this->thirdpartycontent->getType() !== Constant::THIRD_PARTY_CONTENT_TYPE_WEATHER_INFORMATION) {
      throw new DarkSkyException('Wrong third-party content type.');
    }
    $this->key = Drupal::config('effective_activism.settings')->get('darksky_api_key');
    $this->thirdpartycontent = $third_party_content;
    $this->latitude = $third_party_content->get('field_latitude')->value;
    $this->longitude = $third_party_content->get('field_longitude')->value;
    $this->time = $third_party_content->get('field_timestamp')->value;
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
      !empty($this->latitude) &&
      !empty($this->time)
    ) {
      try {
        $url = sprintf('%s/%s/%s,%s,%s?units=%s', self::API_URL, $this->key, $this->latitude, $this->longitude, $this->time, self::UNITS);
        $request = Drupal::httpClient()->get($url);
        $response = $request->getBody()->getContents();
        if (!empty($response)) {
          $data = json_decode($response);
          if (
            json_last_error() === JSON_ERROR_NONE &&
            !empty($data->daily->data)
          ) {
            $record = array_pop($data->daily->data);
            $this->thirdpartycontent->field_precipitation_type = isset($record->precipType) ? $record->precipType : NULL;
            $this->thirdpartycontent->field_precipitation_intensity = isset($record->precipIntensity) ? $record->precipIntensity : NULL;
            $this->thirdpartycontent->field_windspeed = isset($record->windSpeed) ? $record->windSpeed : NULL;
            $this->thirdpartycontent->field_visibility = isset($record->visibility) ? $record->visibility : NULL;
            $minimum_temperature = isset($record->temperatureMin) ? $record->temperatureMin : NULL;
            $maximum_temperature = isset($record->temperatureMax) ? $record->temperatureMax : NULL;
            // Calculate the midrange temperature for the day.
            if (
              !empty($minimum_temperature) &&
              !empty($maximum_temperature)
            ) {
              $this->thirdpartycontent->field_temperature = ($minimum_temperature + $maximum_temperature) / 2;
            }
          }
          else {
            throw new DarkSkyException(sprintf('Unexpected format on JSON string: %s', substr($response, 0, 1000)));
          }
        }
        else {
          throw new DarkSkyException('Empty JSON string.');
        }
      }
      catch (BadResponseException $exception) {
        throw new DarkSkyException($exception->getMessage());
      }
      catch (RequestException $exception) {
        throw new DarkSkyException($exception->getMessage());
      }
      catch (ClientException $exception) {
        throw new DarkSkyException($exception->getMessage());
      }
    }
    // Save third-party content entity.
    parent::request();
  }

  /**
   * {@inheritdoc}
   */
  public static function status() {
    $calls = NULL;
    try {
      $url = sprintf('%s/%s/%s,%s', self::API_URL, Drupal::config('effective_activism.settings')->get('darksky_api_key'), '42.3601', '-71.0589');
      $request = Drupal::httpClient()->get($url);
      $header = $request->getHeader('X-Forecast-API-Calls');
      if (!empty($header) && is_array($header) && is_numeric($header[0])) {
        $calls = $header[0];
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
      }
      else {
        $status = REQUIREMENT_ERROR;
        $description = 'Failed to read response header from Dark Sky API';
      }
    }
    catch (BadResponseException $exception) {
      $status = REQUIREMENT_ERROR;
      $description = 'Bad response exception: ' . $exception->getMessage();
    }
    catch (RequestException $exception) {
      $status = REQUIREMENT_ERROR;
      $description = 'Request exception: ' . $exception->getMessage();
    }
    catch (ClientException $exception) {
      $status = REQUIREMENT_ERROR;
      $description = 'Client exception: ' . $exception->getMessage();
    }
    return [
      'title' => 'Third-party content API Dark Sky',
      'value' => $calls,
      'description' => $description,
      'severity' => $status,
    ];
  }

}
