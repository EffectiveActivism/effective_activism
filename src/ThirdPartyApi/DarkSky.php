<?php

namespace Drupal\effective_activism\ThirdPartyApi;

use Drupal;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Entity\ThirdPartyContent;
use GuzzleHttp\Exception\ClientException;

class DarkSky extends ThirdPartyApi {

  const API_URL = 'https://api.darksky.net/forecast';

  const UNITS = 'si';

  private $key;

  private $latitude;

  private $longitude;

  private $time;

  /**
   * Constructor.
   *
   * @param ThirdPartyContent $third_party_content
   *   The entity to populate with API data.
   * @param array $values
   *   An array of values for the API.
   *
   */
  public function __construct(ThirdPartyContent $third_party_content) {
    $this->key = Drupal::config('effective_activism.settings')->get('darksky_api_key');
    if (empty($this->key)) {
      return FALSE;
    }
    $this->third_party_content = $third_party_content;
    if ($this->third_party_content->getType() !== Constant::THIRD_PARTY_CONTENT_TYPE_WEATHER_INFORMATION) {
      return FALSE;
    }
    $this->latitude = $third_party_content->get('field_latitude')->value;
    $this->longitude = $third_party_content->get('field_longitude')->value;
    $this->time = $third_party_content->get('field_timestamp')->value;
    return $this;
  }

  /**
   * @{inheritdoc}
   */
  public function request() {
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
          print_r($record);
          $this->third_party_content->field_precipitation_type = isset($record->precipType) ? $record->precipType : NULL;
          $this->third_party_content->field_precipitation_intensity = isset($record->precipIntensity) ? $record->precipIntensity : NULL;
          $this->third_party_content->field_windspeed = isset($record->windSpeed) ? $record->windSpeed : NULL;
          $this->third_party_content->field_visibility = isset($record->visibility) ? $record->visibility : NULL;
          $minimum_temperature = isset($record->temperatureMin) ? $record->temperatureMin : NULL;
          $maximum_temperature = isset($record->temperatureMax) ? $record->temperatureMax : NULL;
          // Calculate the midrange temperature for the day.
          if (
            !empty($minimum_temperature) &&
            !empty($maximum_temperature)
          ) {
            $this->third_party_content->field_temperature = $minimum_temperature + $maxium_temperature / 2;
          }
          $this->third_party_content->setPublished(TRUE);
          // Save third-party content entity.
          parent::request();
        }
        else {
          throw new DarkSkyException('Malformed JSON string.');
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
}
