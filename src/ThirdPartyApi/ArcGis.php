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

  const API_INFO = 'https://arcgis.com/sharing/rest/portals/self';

  // The maximum credit usage per month.
  const API_MAX = 50.0;

  // A warning threshold for credit usage.
  const API_THRESHOLD = 10.0;

  // Access tokens are valid for two weeks.
  const API_TOKEN_LIFETIME = 1209600;

  // The diameter to get demographics from.
  const AREA_DIAMETER = 1;

  /**
   * ArcGIS client id.
   *
   * @var string
   */
  private $clientid;

  /**
   * ArcGIS client secret.
   *
   * @var string
   */
  private $secret;

  /**
   * ArcGIS token.
   *
   * @var string
   */
  private $token;

  /**
   * Token timestamp.
   *
   * @var int
   */
  private $timestamp;

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
    if ($this->thirdpartycontent->getType() !== Constant::THIRD_PARTY_CONTENT_TYPE_DEMOGRAPHICS) {
      throw new ArcGisException('Wrong third-party content type.');
    }
    $this->clientid = Drupal::config('effective_activism.settings')->get('arcgis_client_id');
    $this->secret = Drupal::config('effective_activism.settings')->get('arcgis_client_secret');
    $this->token = Drupal::config('effective_activism.settings')->get('arcgis_access_token');
    $this->timestamp = Drupal::config('effective_activism.settings')->get('arcgis_access_token_timestamp');
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
      !empty($this->clientid) &&
      !empty($this->secret) &&
      !empty($this->latitude) &&
      !empty($this->latitude)
    ) {
      try {
        // If access token is empty or expired, request a new.
        if (empty($this->token) || $this->timestamp + self::API_TOKEN_LIFETIME < time()) {
          $request = Drupal::httpClient()->request(
            'POST',
            self::AUTH_URL,
            [
              'form_params' => [
                'f' => 'json',
                'client_id' => $this->clientid,
                'client_secret' => $this->secret,
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
              $this->token = $data->access_token;
              // Update access token and timestamp setting.
              $effective_activism_settings = Drupal::configFactory()->getEditable('effective_activism.settings');
              $effective_activism_settings
                ->set('arcgis_access_token', $this->token)
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
              'token' => $this->token,
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
            $this->thirdpartycontent->source = [
              'uri' => 'https://www.arcgis.com',
              'title' => t('ArcGis'),
            ];
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

  /**
   * {@inheritdoc}
   */
  public static function status() {
    $credits = NULL;
    try {
      $request = Drupal::httpClient()->request(
        'POST',
        self::API_INFO,
        [
          'form_params' => [
            'f' => 'json',
            'token' => Drupal::config('effective_activism.settings')->get('arcgis_access_token'),
          ],
        ]
      );
      $response = $request->getBody()->getContents();
      if (!empty($response)) {
        $data = json_decode($response);
        if (
          json_last_error() === JSON_ERROR_NONE &&
          !empty($data->subscriptionInfo->availableCredits)
        ) {
          $credits = $data->subscriptionInfo->availableCredits;
          if ($credits >= self::API_THRESHOLD) {
            $status = REQUIREMENT_OK;
            $description = sprintf('You have %d credits out of %d left for this month.', $credits, self::API_MAX);
          }
          elseif ($credits = 0) {
            $status = REQUIREMENT_ERROR;
            $description = sprintf('You have no credits left for this month. No more API calls can be made.');
          }
          elseif ($credits < self::API_THRESHOLD) {
            $status = REQUIREMENT_WARNING;
            $description = sprintf('You have less than %d credits left for this month. %d out of %d remain.', self::API_THRESHOLD, $credits, self::API_MAX);
          }
        }
        else {
          $status = REQUIREMENT_ERROR;
          if (isset($data->error->message)) {
            $description = sprintf('ArcGIS API error: %s', $data->error->message);
          }
          else {
            $description = 'Failed to parse JSON response from ArcGIS API';
          }
        }
      }
      else {
        $status = REQUIREMENT_ERROR;
        $description = 'Empty JSON response from ArcGIS API';
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
      'title' => 'Third-party content API ArcGIS',
      'value' => $credits,
      'description' => $description,
      'severity' => $status,
    ];
  }

}
