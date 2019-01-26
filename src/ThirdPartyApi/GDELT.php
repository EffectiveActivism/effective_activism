<?php

namespace Drupal\effective_activism\ThirdPartyApi;

use Drupal;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Entity\ThirdPartyContent;
use Google\Cloud\Core\Exception\GoogleException;
use Google\Cloud\Core\Exception\ServiceException;
use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\BigQuery\Exception\JobException;

/**
 * Implements a wrapper class for the GDELT API.
 */
class GDELT extends ThirdPartyApi {

  // The GDELT project on Google BigQuery.
  const GOOGLE_BIGQUERY_PROJECT_NAME = 'gdelt-bq';
  const GOOGLE_BIGQUERY_DATABASE_NAME = 'gdeltv2';
  const GOOGLE_BIGQUERY_TABLE_NAME = 'events';
  const GOOGLE_BIGQUERY_SQLDATE = 'Ymd';

  // THe offset in meters that defines the bounding box.
  const AREA_OFFSET = 5000;

  // The windows of news.
  const TIME_WINDOW = 60 * 60 * 24 * 7;

  // https://en.wikipedia.org/wiki/Earth_radius.
  const EARTH_RADIUS = 6371000;

  // The limit of stored news sources.
  const MAX_NEWS_SOURCES = 10;

  // Goldenstein scale values.
  const GOLDENSTEIN_SCALE = [
    'extreme_conflict',
    'high_conflict',
    'moderate_conflict',
    'light_conflict',
    'neutral',
    'light_cooperation',
    'moderate_cooperation',
    'high_cooperation',
    'extreme_cooperation',
  ];

  // Tone values.
  const TONE = [
    'extremely_negative',
    'highly_negative',
    'moderately_negative',
    'lightly_negative',
    'neutral',
    'lightly_positive',
    'moderately_positive',
    'highly_positive',
    'extremely_positive',
  ];

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
   * Timestamp.
   *
   * @var int
   */
  private $time;

  /**
   * {@inheritdoc}
   */
  public function __construct(ThirdPartyContent $third_party_content = NULL) {
    parent::__construct($third_party_content);
    if ($this->thirdpartycontent->getType() !== Constant::THIRD_PARTY_CONTENT_TYPE_CITY_PULSE) {
      throw new GDELTException('Wrong third-party content type.');
    }
    $this->projectId = Drupal::config('effective_activism.settings')->get('google_bigquery_api_project_id');
    $this->key = Drupal::config('effective_activism.settings')->get('google_bigquery_api_key');
    $this->thirdpartycontent = $third_party_content;
    $this->bigQuery = new BigQueryClient([
      'projectId' => $this->projectId,
      'keyFile' => $this->key,
    ]);
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
      // Calculate bounding box.
      $offset_latitude = self::AREA_OFFSET / self::EARTH_RADIUS;
      $offset_longitude = self::AREA_OFFSET / (self::EARTH_RADIUS * cos(pi() * $this->latitude / 180));
      $bounding_box_latitude_north = $this->latitude + $offset_latitude * 180 / pi();
      $bounding_box_latitude_south = $this->latitude - $offset_latitude * 180 / pi();
      $bounding_box_longitude_west = $this->longitude - $offset_longitude * 180 / pi();
      $bounding_box_longitude_east = $this->longitude + $offset_longitude * 180 / pi();
      try {
        // Run a query and inspect the results.
        $query = sprintf('SELECT AVG(GoldsteinScale), AVG(AvgTone), GROUP_CONCAT_UNQUOTED(SOURCEURL, \' \') FROM [%s.%s.%s] WHERE
          SQLDATE <= %d AND
          SQLDATE > %d AND (
            (Actor1Geo_Lat < %F AND Actor1Geo_Lat > %F AND Actor1Geo_Long > %F AND Actor1Geo_Long < %F)
            OR
            (Actor2Geo_Lat < %F AND Actor2Geo_Lat > %F AND Actor2Geo_Long > %F AND Actor2Geo_Long < %F)
          ) LIMIT 1000;',
          self::GOOGLE_BIGQUERY_PROJECT_NAME,
          self::GOOGLE_BIGQUERY_DATABASE_NAME,
          self::GOOGLE_BIGQUERY_TABLE_NAME,
          date(self::GOOGLE_BIGQUERY_SQLDATE, $this->time),
          date(self::GOOGLE_BIGQUERY_SQLDATE, $this->time - self::TIME_WINDOW),
          $bounding_box_latitude_north,
          $bounding_box_latitude_south,
          $bounding_box_longitude_west,
          $bounding_box_longitude_east,
          $bounding_box_latitude_north,
          $bounding_box_latitude_south,
          $bounding_box_longitude_west,
          $bounding_box_longitude_east
        );
        $queryJobConfig = $this->bigQuery->query($query);
        // Enable legacy SQL to use the GROUP_CONCAT_UNQUOTED function.
        $queryJobConfig->useLegacySql(TRUE);
        $queryResults = $this->bigQuery->runQuery($queryJobConfig);
        $row = $queryResults->getIterator()->current();
        if (isset($row['f0_']) && is_numeric($row['f0_'])) {
          switch (TRUE) {

            // Extreme conflict.
            case $row['f0_'] < -80:
              $this->thirdpartycontent->field_goldenstein_scale = self::GOLDENSTEIN_SCALE[0];
              break;

            // High conflict.
            case $row['f0_'] < -60:
              $this->thirdpartycontent->field_goldenstein_scale = self::GOLDENSTEIN_SCALE[1];
              break;

            // Moderate conflict.
            case $row['f0_'] < -30:
              $this->thirdpartycontent->field_goldenstein_scale = self::GOLDENSTEIN_SCALE[2];
              break;

            // Light conflict.
            case $row['f0_'] < -5:
              $this->thirdpartycontent->field_goldenstein_scale = self::GOLDENSTEIN_SCALE[3];
              break;

            // Neutral.
            case $row['f0_'] < 5:
              $this->thirdpartycontent->field_goldenstein_scale = self::GOLDENSTEIN_SCALE[4];
              break;

            // Light cooperation.
            case $row['f0_'] < 30:
              $this->thirdpartycontent->field_goldenstein_scale = self::GOLDENSTEIN_SCALE[5];
              break;

            // Moderate cooperation.
            case $row['f0_'] < 60:
              $this->thirdpartycontent->field_goldenstein_scale = self::GOLDENSTEIN_SCALE[6];
              break;

            // High cooperation.
            case $row['f0_'] < 80:
              $this->thirdpartycontent->field_goldenstein_scale = self::GOLDENSTEIN_SCALE[7];
              break;

            // Extreme cooperation.
            case $row['f0_'] <= 100:
              $this->thirdpartycontent->field_goldenstein_scale = self::GOLDENSTEIN_SCALE[8];
              break;
          }
        }
        if (isset($row['f1_']) && is_numeric($row['f1_'])) {
          switch (TRUE) {

            // Extremely negative.
            case $row['f1_'] < -80:
              $this->thirdpartycontent->field_tone = self::TONE[0];
              break;

            // Highly negative.
            case $row['f1_'] < -60:
              $this->thirdpartycontent->field_tone = self::TONE[1];
              break;

            // Moderately negative.
            case $row['f1_'] < -30:
              $this->thirdpartycontent->field_tone = self::TONE[2];
              break;

            // Lightly negative.
            case $row['f1_'] < -5:
              $this->thirdpartycontent->field_tone = self::TONE[3];
              break;

            // Neutral.
            case $row['f1_'] < 5:
              $this->thirdpartycontent->field_tone = self::TONE[4];
              break;

            // Lightly positive.
            case $row['f1_'] < 30:
              $this->thirdpartycontent->field_tone = self::TONE[5];
              break;

            // Moderately positive.
            case $row['f1_'] < 60:
              $this->thirdpartycontent->field_tone = self::TONE[6];
              break;

            // Highly positive.
            case $row['f1_'] < 80:
              $this->thirdpartycontent->field_tone = self::TONE[7];
              break;

            // Extremely positive.
            case $row['f1_'] <= 100:
              $this->thirdpartycontent->field_tone = self::TONE[8];
              break;
          }
        }
        $this->thirdpartycontent->field_news_sources = isset($row['f2_']) ? array_slice(array_unique(explode(' ', $row['f2_'])), 0, self::MAX_NEWS_SOURCES) : NULL;
        $this->thirdpartycontent->source = [
          'uri' => 'https://www.gdeltproject.org/about.html',
          'title' => t('GDELT'),
        ];
      }
      catch (GoogleException $exception) {
        throw new GDELTException($exception->getMessage());
      }
      catch (ServiceException $exception) {
        throw new GDELTException($exception->getMessage());
      }
      catch (JobException $exception) {
        throw new GDELTException($exception->getMessage());
      }
    }
    // Save third-party content entity.
    parent::request();
  }

  /**
   * {@inheritdoc}
   */
  public static function status() {
    return [
      'title' => 'Third-party content API GDELT',
      'value' => 0,
      'description' => 'Daily query size limit is unlimited by default',
      'severity' => REQUIREMENT_OK,
    ];
  }

}
