<?php

namespace Drupal\effective_activism\ThirdPartyApi;

use Drupal;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Entity\ThirdPartyContent;
use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\BigQuery\Exception\JobException;

/**
 * Implements a wrapper class for the GDELT API.
 */
class GDELT extends ThirdPartyApi {

  const AUTH_URL = 'https://www.arcgis.com/sharing/rest/oauth2/token/';

  const API_URL = 'https://geoenrich.arcgis.com/arcgis/rest/services/World/geoenrichmentserver/Geoenrichment/Enrich';

  const API_INFO = 'https://arcgis.com/sharing/rest/portals/self';

  // The diameter to get demographics from.
  const AREA_DIAMETER = 1;

  /**
   * Google BigQuery API project ID.
   *
   * @var string
   */
  private $projectId;

  /**
   * Google BigQuery API key.
   *
   * @var string
   */
  private $key;

  /**
   * BigQuery object.
   *
   * @var \Google\Cloud\BigQuery\BigQueryClient
   */
  private $bigQuery;

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
  public function __construct(ThirdPartyContent $third_party_content = NULL) {
    //parent::__construct($third_party_content);
    //if ($this->thirdpartycontent->getType() !== Constant::THIRD_PARTY_CONTENT_TYPE_DEMOGRAPHICS) {
    //  throw new GDELTException('Wrong third-party content type.');
    //}
    $this->projectId = Drupal::config('effective_activism.settings')->get('google_bigquery_api_project_id');
    $this->key = Drupal::config('effective_activism.settings')->get('google_bigquery_api_key');
    //$this->thirdpartycontent = $third_party_content;
    $this->bigQuery = new BigQueryClient([
      'projectId' => $this->projectId,
      'keyFile' => $this->key,
    ]);
    //$this->latitude = $third_party_content->get('field_latitude')->value;
    //$this->longitude = $third_party_content->get('field_longitude')->value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function request() {
    // Proceed only if the required data is available.
    if (
      !empty($this->key) //&&
      //!empty($this->latitude) &&
      //!empty($this->latitude)
    ) {
      try {
        // Run a query and inspect the results.
        $query = 'SELECT AVG(GoldsteinScale), AVG(AvgTone) FROM [gdelt-bq:gdeltv2.events] WHERE SQLDATE = 20180102 AND Actor1Geo_Lat > 52.3 AND Actor1Geo_Lat < 52.7 AND Actor1Geo_Long < 13.4 AND Actor1Geo_Long > 13.1;';
        $queryJobConfig = $this->bigQuery->query($query);
        $queryResults = $this->bigQuery->runQuery($queryJobConfig);

        foreach ($queryResults as $row) {
          Drupal::logger('debug')->notice('<pre>' . print_r($row, TRUE) . '</pre>');
        }
      }
      catch (JobException $exception) {
        throw new GDELTException($exception->getMessage());
      }
    }
    // Save third-party content entity.
    //parent::request();
  }

  /**
   * {@inheritdoc}
   */
  public static function status() {

    return [
      'title' => 'Third-party content API GDELT',
      'value' => 0,
      'description' => '',
      'severity' => REQUIREMENT_OK,
    ];
  }

}
