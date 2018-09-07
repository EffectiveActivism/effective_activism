<?php

namespace Drupal\effective_activism\ContentMigration\Import\ICalendar;

use DateTime;
use Drupal;
use Drupal\effective_activism\ContentMigration\Import\EntityImportParser;
use Drupal\effective_activism\ContentMigration\ParserInterface;
use Drupal\effective_activism\ContentMigration\ParserValidationException;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Import;
use Drupal\effective_activism\Helper\LocationHelper;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;

/**
 * Parses ICalendar.
 *
 * Rewritten from https://github.com/MartinThoma/ics-parser/.
 */
class ICalendarParser extends EntityImportParser implements ParserInterface {

  const BATCHSIZE = 50;
  const ICALENDAR_DATETIME_FORMAT = 'Ymd\THis';
  const INVALID_PATH = -11;

  /**
   * Any validation error message.
   *
   * @var array
   */
  private $errorMessage;

  /**
   * Parent group.
   *
   * @var \Drupal\effective_activism\Entity\Group
   */
  private $group;

  /**
   * Import entity.
   *
   * @var \Drupal\effective_activism\Entity\Import
   */
  private $import;

  /**
   * The number of items.
   *
   * @var int
   */
  private $itemCount;

  /**
   * The items to process.
   *
   * @var array
   */
  private $items;

  /**
   * The unprocessed lines of the iCalendar file.
   *
   * @var array
   */
  private $lines;

  /**
   * Creates the ICalendarParser Object.
   *
   * @param string $url
   *   An ICalendar URL.
   * @param \Drupal\effective_activism\Entity\Group $group
   *   The parent group.
   * @param \Drupal\effective_activism\Entity\Import $import
   *   The import entity.
   */
  public function __construct($url, Group $group, Import $import = NULL) {
    $this->group = $group;
    $this->import = $import;
    try {
      // Convert webcal scheme to http, as Guzzler may not support webcal.
      $url = strpos($url, 'webcal://') === 0 ? str_replace('webcal://', 'http://', $url) : $url;
      // Retrieve url.
      $response = Drupal::httpClient()->get($url);
      if ($response->getStatusCode() === 200) {
        $this->lines = explode("\n", $response->getBody()->getContents());
        $this->initialize();
      }
      else {
        throw new ParserValidationException(self::INVALID_PATH, NULL, NULL, NULL);
      }
    }
    catch (BadResponseException $exception) {
      throw new ParserValidationException(self::INVALID_PATH, NULL, NULL, NULL);
    }
    catch (RequestException $exception) {
      throw new ParserValidationException(self::INVALID_PATH, NULL, NULL, NULL);
    }
    catch (ClientException $exception) {
      throw new ParserValidationException(self::INVALID_PATH, NULL, NULL, NULL);
    }
  }

  /**
   * Extract events from an iCalendar file.
   */
  private function initialize() {
    $position = 0;
    while (isset($this->lines[$position])) {
      $line = $this->lines[$position];
      if ('BEGIN:VEVENT' === trim($line)) {
        // Locate end.
        $end = array_search('END:VEVENT', array_map(function ($line) {
          return trim($line);
        }, array_slice($this->lines, $position, NULL, TRUE)), TRUE);
        if ($end) {
          $slice = array_slice($this->lines, $position, $end - $position + 1);
          $this->items[] = $slice;
          $this->itemCount++;
          $position = $end;
        }
        else {
          return FALSE;
        }
      }
      $position++;
    }
  }

  /**
   * Determines if a line has a key and value.
   *
   * @return bool
   *   Returns TRUE if line has key/value pair, FALSE otherwise.
   */
  private function hasKeyValue($line) {
    return strpos($line, ' ') !== 0 && strpos($line, ':') !== FALSE;
  }

  /**
   * Extracts a key/value pair from a line.
   *
   * @param string $line
   *   A line.
   *
   * @return array
   *   A key/value pair.
   */
  private function extractKeyValue($line) {
    list($key, $value) = explode(':', $line, 2);
    // Strip any extra information.
    $key = strpos($key, ';') !== FALSE ? substr($key, 0, strpos($key, ';')) : $key;
    // Unescape commas.
    $value = preg_replace("/\r|\n/", '', str_replace('\,', ',', $value));
    return [$key, $value];
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $isValid = TRUE;
    try {
      $this->validateHeader($this->lines);
      $this->validateItems($this->lines);
    }
    catch (ParserValidationException $exception) {
      $isValid = FALSE;
      switch ($exception->getErrorCode()) {
        case self::INVALID_HEADERS:
          $this->errorMessage = t('The iCalendar file is not recognized.');
          break;

        case self::INVALID_DATE:
          $this->errorMessage = t('The iCalendar file contains an event with an invalid date.');
          break;

        case self::INVALID_EVENT:
          $this->errorMessage = t('The iCalendar file contains an invalid event.');
          break;
      }
    }
    return $isValid;
  }

  /**
   * Validate header.
   *
   * @param array $lines
   *   The raw iCalendar file.
   */
  private function validateHeader(array $lines) {
    if (!preg_match("/BEGIN:VCALENDAR.*VERSION:[12]\.0.*END:VCALENDAR/s", implode("\n", $lines))) {
      throw new ParserValidationException(self::INVALID_HEADERS);
    }
  }

  /**
   * Validate events.
   *
   * @param array $lines
   *   The raw iCalendar file.
   */
  private function validateItems(array $lines) {
    $position = 0;
    while (isset($lines[$position])) {
      $line = $lines[$position];
      if ('BEGIN:VEVENT' === trim($line)) {
        // Locate end.
        $end = array_search('END:VEVENT', array_map(function ($line) {
          return trim($line);
        }, array_slice($this->lines, $position, NULL, TRUE)), TRUE);
        if ($end) {
          $slice = array_slice($this->lines, $position, $end - $position + 1);
          $this->validateItem($slice);
          $position = $end;
        }
        else {
          return FALSE;
        }
      }
      $position++;
    }
  }

  /**
   * Validate an event.
   *
   * @param array $slice
   *   A slice of an iCalendar file that describes an event.
   */
  private function validateItem(array $slice) {
    $event = [];
    $slice_position = 0;
    // Create event, if any.
    while (isset($slice[$slice_position])) {
      $line = $slice[$slice_position];
      if ($this->hasKeyValue($line)) {
        list($key, $value) = $this->extractKeyValue($line);
        // Read ahead to capture multi-line values.
        $read_ahead = $slice_position + 1;
        while (isset($slice[$read_ahead])) {
          if ($this->hasKeyValue($slice[$read_ahead])) {
            break;
          }
          $value .= ltrim(preg_replace("/\r|\n/", '', str_replace('\,', ',', $slice[$read_ahead])));
          $read_ahead++;
        }
        switch ($key) {
          case 'DTSTART':
          case 'DTEND':
            // Remove the 'Z' from date.
            $value = strpos($value, 'Z') === strlen($value) - 1 ? substr($value, 0, strlen($value) - 1) : $value;
            $date = DateTime::createFromFormat(self::ICALENDAR_DATETIME_FORMAT, $value);
            if (!$date || $date->format(self::ICALENDAR_DATETIME_FORMAT) !== $value) {
              throw new ParserValidationException(self::INVALID_DATE, NULL, NULL, $value);
            }
            break;
        }
      }
      $slice_position++;
    }
    $event = $this->extractEvent($slice);
    if ($event !== FALSE) {
      if (!$this->validateEvent([
        $event['title'],
        $event['start_date'],
        $event['end_date'],
        $event['location'],
        $event['description'],
        NULL,
        NULL,
        NULL,
        $this->group->id(),
        $event['external_uid'],
        NULL,
        NULL,
        NULL,
      ])) {
        throw new ParserValidationException(self::INVALID_EVENT, NULL, NULL);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getErrorMessage() {
    return $this->errorMessage;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemCount() {
    return $this->itemCount;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextBatch($position) {
    return array_slice($this->items, $position, self::BATCHSIZE);
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    $event = $this->extractEvent($item);
    // Create event.
    if ($event !== FALSE) {
      $this->latestEvent = $this->importEvent([
        $event['title'],
        $event['start_date'],
        $event['end_date'],
        $event['location'],
        $event['description'],
        NULL,
        NULL,
        NULL,
        $this->group->id(),
        $event['external_uid'],
        $this->import->id(),
        NULL,
        NULL,
      ]);
    }
  }

  /**
   * Extract event from a slice of an iCalendar file.
   *
   * @param array $slice
   *   The slice to extract an event from.
   *
   * @return array
   *   A unique event.
   */
  private function extractEvent(array $slice) {
    // Populate event array.
    $event = [
      'title' => NULL,
      'description' => NULL,
      'start_date' => NULL,
      'end_date' => NULL,
      'location' => [
        'address' => NULL,
        'extra_information' => NULL,
      ],
    ];
    $slice_position = 0;
    // Create event, if any.
    while (isset($slice[$slice_position])) {
      $line = $slice[$slice_position];
      if ($this->hasKeyValue($line)) {
        list($key, $value) = $this->extractKeyValue($line);
        // Read ahead to capture multi-line values.
        $read_ahead = $slice_position + 1;
        while (isset($slice[$read_ahead])) {
          if ($this->hasKeyValue($slice[$read_ahead])) {
            break;
          }
          $next_line = str_replace(["\r", "\n"], '', str_replace('\,', ',', $slice[$read_ahead]));
          $value .= substr($next_line, 1);
          $read_ahead++;
        }
        switch ($key) {
          case 'DTSTART':
            // Remove the 'Z' from date.
            $value = strpos($value, 'Z') === strlen($value) - 1 ? substr($value, 0, strlen($value) - 1) : $value;
            $event['start_date'] = DateTime::createFromFormat(self::ICALENDAR_DATETIME_FORMAT, $value)->format(DATETIME_DATETIME_STORAGE_FORMAT);
            break;

          case 'DTEND':
            // Remove the 'Z' from date.
            $value = strpos($value, 'Z') === strlen($value) - 1 ? substr($value, 0, strlen($value) - 1) : $value;
            $event['end_date'] = DateTime::createFromFormat(self::ICALENDAR_DATETIME_FORMAT, $value)->format(DATETIME_DATETIME_STORAGE_FORMAT);
            break;

          case 'LOCATION':
            $address = NULL;
            $extra_information = NULL;
            if (LocationHelper::validateAddress($value)) {
              $address = $value;
            }
            else {
              $extra_information = $value;
            }
            $event['location'] = [
              'address' => $address,
              'extra_information' => $extra_information,
            ];
            break;

          case 'SUMMARY':
            $event['title'] = $value;
            break;

          case 'DESCRIPTION':
            $event['description'] = str_replace('\n', "\n", $value);
            break;

          case 'UID':
            $event['external_uid'] = $value;
            break;
        }
      }
      $slice_position++;
    }
    // Only include event if it isn't imported already.
    if (!empty($event['external_uid'])) {
      $count = Drupal::entityQuery('event')
        ->condition('external_uid', $event['external_uid'])
        ->condition('parent', $this->group->id())
        ->count()
        ->execute();
      if (!empty($count) && $count > 0) {
        return FALSE;
      }
    }
    return $event;
  }

}
