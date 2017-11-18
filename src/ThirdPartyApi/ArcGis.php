<?php

namespace Drupal\effective_activism\ThirdPartyApi;

use Drupal;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Entity\ThirdPartyContent;
use GuzzleHttp\Exception\ClientException;

/**
 * Implements a wrapper class for the ArcGis API.
 */
class ArcGis extends ThirdPartyApi {

  const AUTH_URL = 'https://www.arcgis.com/sharing/rest/oauth2/token/';

  const API_URL = 'https://geoenrich.arcgis.com/arcgis/rest/services/World/geoenrichmentserver/Geoenrichment/Enrich';

  // Access tokens are valid for two weeks.
  const API_TOKEN_LIFETIME = 1209600;

  // The diameter to get demographics from.
  const AREA_DIAMETER = 1;

  private $client_id;

  private $client_secret;

  private $access_token;

  private $access_token_timestamp;

  private $latitude;

  private $longitude;

  /**
   * {@inheritdoc}
   */
  public function __construct(ThirdPartyContent $third_party_content) {
    parent::__construct($third_party_content);
    if ($this->thirdpartycontent->getType() !== Constant::THIRD_PARTY_CONTENT_TYPE_DEMOGRAPHICS) {
      throw new ArcGisException('Wrong third-party content type.');
    }
    $this->client_id = Drupal::config('effective_activism.settings')->get('arcgis_client_id');
    $this->client_secret = Drupal::config('effective_activism.settings')->get('arcgis_client_secret');
    $this->access_token = Drupal::config('effective_activism.settings')->get('arcgis_access_token');
    $this->access_token_timestamp = Drupal::config('effective_activism.settings')->get('arcgis_access_token_timestamp');
    
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
      !empty($this->client_id) &&
      !empty($this->client_secret) &&
      !empty($this->latitude) &&
      !empty($this->latitude)
    ) {
      try {
        // If access token is empty or expired, request a new.
        if (empty($this->access_token) || $this->access_token_timestamp + self::API_TOKEN_LIFETIME < time()) {
          $request = Drupal::httpClient()->request(
            'POST',
            self::AUTH_URL,
            [
              'form_params' => [
                'f' => 'json',
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type' => 'client_credentials',
                'expiration' => (int) floor(self::API_TOKEN_LIFETIME / 60),
              ],
            ]
          );
          $response = $request->getBody()->getContents();
          if (!empty($response)) {
            $data = json_decode($response);
            if (
              json_last_error() === JSON_ERROR_NONE &&
              !empty($data->access_token)
            ) {
              $this->access_token = $data->access_token;
              // Update access token and timestamp setting.
              $effective_activism_settings = Drupal::configFactory()->getEditable('effective_activism.settings');
              $effective_activism_settings
                ->set('arcgis_access_token', $this->access_token)
                ->set('arcgis_access_token_timestamp', time())
                ->save(TRUE);
            }
            else {
              throw new ArcGisException(sprintf('Unexpected format on JSON string: %s', substr($response, 0, 1000)));
            }
          }
          else {
            throw new ArcGisException('Empty JSON string.');
          }
        }
        $request = Drupal::httpClient()->request(
          'POST',
          self::API_URL,
          [
            'form_params' => [
              'f' => 'json',
              'token' => $this->access_token,
              'inSR' => 4326,
              'outSR' => 4326,
              'returnGeometry' => 'true',
              'studyAreas' => sprintf('[{"geometry":{"x":%s,"y":%s}}]', $this->longitude, $this->latitude),
              'studyAreasOptions' => sprintf('{"areaType":"RingBuffer","bufferUnits":"esriKilometers","bufferRadii":[%d]}', self::AREA_DIAMETER),
              'dataCollections' => '["KeyGlobalFacts", "KeyUSFacts"]',
            ],
          ]
        );
        $response = $request->getBody()->getContents();
        if (!empty($response)) {
          $data = json_decode($response);
          if (
            json_last_error() === JSON_ERROR_NONE &&
            !empty($data->results)
          ) {
            $result = array_pop($data->results);
            $feature_set = array_pop($result->value->FeatureSet);
            $features = array_pop($feature_set->features);
            $attributes = $features->attributes;
            $this->thirdpartycontent->field_total_population = isset($attributes->TOTPOP) ? $attributes->TOTPOP : NULL;
            $this->thirdpartycontent->field_total_households = isset($attributes->TOTHH) ? $attributes->TOTHH : NULL;
            $this->thirdpartycontent->field_average_household_size = isset($attributes->AVGHHSZ) ? $attributes->AVGHHSZ : NULL;
            $this->thirdpartycontent->field_male_population = isset($attributes->TOTMALES) ? $attributes->TOTMALES : NULL;
            $this->thirdpartycontent->field_female_population = isset($attributes->TOTFEMALES) ? $attributes->TOTFEMALES : NULL;
          }
          else {
            throw new ArcGisException(sprintf('Unexpected format on JSON string: %s', substr($response, 0, 1000)));
          }
        }
        else {
          throw new ArcGisException('Empty JSON string.');
        }
      }
      catch (BadResponseException $exception) {
        throw new ArcGisException($exception->getMessage());
      }
      catch (RequestException $exception) {
        throw new ArcGisException($exception->getMessage());
      }
      catch (ClientException $exception) {
        throw new ArcGisException($exception->getMessage());
      }
    }
    // Save third-party content entity.
    parent::request();
  }

}