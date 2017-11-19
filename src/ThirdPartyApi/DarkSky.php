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

  const UNITS = 'si';

  private $key;

  private $latitude;

  private $longitude;

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

}
