<?php

namespace Drupal\effective_activism\Helper\ImportParser;

use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Import;
use Drupal\effective_activism\Helper\LocationHelper;

/**
 * Parses ICalendar.
 *
 * Rewritten from https://github.com/MartinThoma/ics-parser/.
 */
class ICalendarParser extends EntityParser implements ParserInterface {

  const BATCHSIZE = 50;
  const ICALENDAR_DATETIME_FORMAT = 'Ymd\THis';

  /**
   * Parent group.
   *
   * @var Group
   */
  private $group;

  /**
   * Import entity
   *
   * @var Import
   */
  private $import;

  /**
   * The raw calendar.
   *
   * @var string
   */
  private $raw;

  /**
   * The parsed calendar.
   *
   * @var array
   */
  private $cal;

  /**
   * How many events are in this iCal?
   *
   * @var int
   */
  private $eventCount = 0;

  /**
   * Which keyword has been added to cal at last?
   *
   * @var string
   */
  private $lastKeyword;

  /**
   * Filters to apply.
   *
   * @var array
   */
  private $filters;

  /**
   * Any validation error message.
   *
   * @var array
   */
  private $errorMessage;

  /**
   * Creates the ICalendarParser Object.
   *
   * @param string $url
   *   An ICalendar URL.
   * @param array $filters
   *   Filters to apply.
   * @param Group $group
   *   The parent group.
   * @param Import $import
   *   The import entity.
   */
  public function __construct($url, array $filters, Group $group, Import $import = NULL) {
    if (empty($url)) {
      return FALSE;
    }
    // Convert webcal scheme to http, as Guzzler may not support webcal.
    $count = 1;
    $url = strpos($url, 'webcal://') === 0 ? str_replace('webcal://', 'http://', $url, $count) : $url;
    // Retrieve url.
    $client = \Drupal::httpClient();
    $request = $client->get($url);
    $this->raw = (string) $request->getBody();
    $this->filters = $filters;
    $lines = explode("\n", $this->raw);
    $this->initLines($lines);
    $this->group = $group;
    $this->import = $import;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $isValid = TRUE;
    try {
      $this->validateHeader();
      $this->validateItems();
    }
    catch (ParserValidationException $exception) {
      $isValid = FALSE;
      switch ($exception->getErrorCode()) {
        case self::INVALID_HEADERS:
          $this->errorMessage = t('The ICalendar file is not recognized.');
          break;

        case self::INVALID_DATE:
          $this->errorMessage = t('The ICalendar file contains an event with an incorrect date.');
          break;

        case self::INVALID_EVENT:
          $this->errorMessage = t('The ICalendar file contains an incorrect event.');
          break;

        case self::INVALID_LOCATION:
          $this->errorMessage = t('The ICalendar file contains an event with an incorrect address.');
          break;

      }
    }
    return $isValid;
  }

  /**
   * Validate headers.
   */
  public function validateHeader() {
    if (!preg_match("/BEGIN:VCALENDAR.*VERSION:[12]\.0.*END:VCALENDAR/s", $this->raw)) {
      throw new ParserValidationException(self::INVALID_HEADERS);
    }
  }

  /**
   * Validate items.
   */
  private function validateItems() {
    if (!empty($this->cal['VEVENT'])) {
      foreach ($this->cal['VEVENT'] as $event) {
        // Apply filters, if any.
        if ((
          // If title doesn't contain string from title filter.
          !empty($this->filters['title']) &&
          !empty($event['SUMMARY']) &&
          strstr($event['SUMMARY'], $this->filters['title']) === FALSE
        ) ||
        (
          // Or description doesn't contain string from description filter.
          !empty($this->filters['description']) &&
          !empty($event['DESCRIPTION']) &&
          strstr($event['DESCRIPTION'], $this->filters['description']) === FALSE
        ) ||
        (
          // Or start date is older than start date filter.
          !empty($this->filters['date_start']) &&
          !empty($event['DSTART']) &&
          strtotime($this->filters['date_start']) < strtotime($event['DTSTART'])
        ) ||
        (
          // Or end date is newer than end date filter.
          !empty($this->filters['date_end']) &&
          !empty($event['DEND']) &&
          strtotime($this->filters['date_end']) > strtotime($event['DEND'])
        )) {
          // ... Then skip event.
          continue;
        }
        $this->validateItem($event);
      }
    }
  }

  /**
   * Validate items.
   *
   * @param array $values
   *   The values to validate.
   */
  private function validateItem(array $values) {
    foreach ($values as $key => $value) {
      switch ($key) {
        case 'DTSTART':
        case 'DTEND':
          $date = \DateTime::createFromFormat(self::ICALENDAR_DATETIME_FORMAT, $value);
          if (!$date || $date->format(self::ICALENDAR_DATETIME_FORMAT) !== $value) {
            throw new ParserValidationException(self::INVALID_DATE);
          }
          break;
      }
    }
    // Validate event if required fields are present.
    if ($this->isEvent($values)) {
      $values = [
        !empty($values['SUMMARY']) ? $values['SUMMARY'] : NULL,
        \DateTime::createFromFormat(self::ICALENDAR_DATETIME_FORMAT, $values['DTSTART'])->format(DATETIME_DATETIME_STORAGE_FORMAT),
        \DateTime::createFromFormat(self::ICALENDAR_DATETIME_FORMAT, $values['DTEND'])->format(DATETIME_DATETIME_STORAGE_FORMAT),
        [
          'address' => NULL,
          'extra_information' => NULL,
        ],
        !empty($values['DESCRIPTION']) ? str_replace(['\n', '\,'], ["\n"], $values['DESCRIPTION']) : NULL,
        NULL,
        $this->group->id(),
        NULL,
        NULL,
      ];
      if (!$this->validateEvent($values)) {
        throw new ParserValidationException(self::INVALID_EVENT);
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
    return $this->eventCount;
  }

  /**
   * Checks if values contains an event.
   *
   * @param array $values
   *   The values to check.
   *
   * @return bool
   *   Whether or not the values contains an event.
   */
  private function isEvent(array $values) {
    return !empty($values['DTSTART']);
  }

  /**
   * {@inheritdoc}
   */
  public function getNextBatch($position) {
    $itemCount = 1;
    $items = [];
    if (!empty($this->cal['VEVENT'])) {
      foreach ($this->cal['VEVENT'] as $event) {
        // Skip to current event.
        if ($itemCount < $position) {
          $itemCount++;
          continue;
        }
        // Apply filters, if any.
        if ((
          // If title doesn't contain string from title filter.
          !empty($this->filters['title']) &&
          !empty($event['SUMMARY']) &&
          strstr($event['SUMMARY'], $this->filters['title']) === FALSE
        ) ||
        (
          // Or description doesn't contain string from description filter.
          !empty($this->filters['description']) &&
          !empty($event['DESCRIPTION']) &&
          strstr($event['DESCRIPTION'], $this->filters['description']) === FALSE
        ) ||
        (
          // Or start date is older than start date filter.
          !empty($this->filters['date_start']) &&
          !empty($event['DSTART']) &&
          strtotime($this->filters['date_start']) < strtotime($event['DTSTART'])
        ) ||
        (
          // Or end date is newer than end date filter.
          !empty($this->filters['date_end']) &&
          !empty($event['DEND']) &&
          strtotime($this->filters['date_end']) > strtotime($event['DEND'])
        )) {
          // ... Then skip event.
          continue;
        }
        // Otherwise, add event.
        $items[] = $event;
        $itemCount++;
        if ($itemCount === $position + self::BATCHSIZE) {
          break;
        }
      }
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function importItem(array $values) {
    // Check if event exists for group.
    if (!empty($values['UID'])) {
      $count = \Drupal::entityQuery('event')
        ->condition('external_uid', $values['UID'])
        ->count()
        ->execute();
      if (!empty($count) && $count > 0) {
        return FALSE;
      }
    }
    $event = $this->importEvent([
      !empty($values['SUMMARY']) ? $values['SUMMARY'] : NULL,
      \DateTime::createFromFormat(self::ICALENDAR_DATETIME_FORMAT, $values['DTSTART'])->format(DATETIME_DATETIME_STORAGE_FORMAT),
      \DateTime::createFromFormat(self::ICALENDAR_DATETIME_FORMAT, $values['DTEND'])->format(DATETIME_DATETIME_STORAGE_FORMAT),
      [
        'address' => !empty($values['LOCATION']) ? $values['LOCATION'] : NULL,
        'extra_information' => NULL,
      ],
      !empty($values['DESCRIPTION']) ? str_replace(['\n', '\,'], ["\n"], $values['DESCRIPTION']) : NULL,
      NULL,
      $this->group->id(),
      !empty($values['UID']) ? $values['UID'] : NULL,
      $this->import->id(),
    ]);
    return $event->id();
  }

  /**
   * Initializes lines from file.
   *
   * @param array $lines
   *   The lines to initialize.
   *
   * @return Object
   *   The iCal Object.
   */
  private function initLines(array $lines) {
    if (stristr($lines[0], 'BEGIN:VCALENDAR') === FALSE) {
      return FALSE;
    }
    else {
      foreach ($lines as $line) {
        // Trim trailing whitespace.
        $line = rtrim($line);
        $add  = $this->keyValueFromString($line);
        if ($add === FALSE) {
          $this->addCalendarComponentWithKeyAndValue($component, FALSE, $line);
          continue;
        }
        $keyword = $add[0];
        // Could be an array containing multiple values.
        $values = $add[1];
        if (!is_array($values)) {
          if (!empty($values)) {
            // Make an array as not already.
            $values = array($values);
            // Empty placeholder array.
            $blank_array = array();
            array_push($values, $blank_array);
          }
          else {
            // Use blank array to ignore this line.
            $values = array();
          }
        }
        elseif (empty($values[0])) {
          // Use blank array to ignore this line.
          $values = array();
        }
        // Reverse so that our array of properties is processed first.
        $values = array_reverse($values);
        foreach ($values as $value) {
          switch ($line) {
            case 'BEGIN:VEVENT':
              if (!is_array($value)) {
                $this->eventCount++;
              }
              $component = 'VEVENT';
              break;

            // All other special strings.
            case 'BEGIN:VCALENDAR':
            case 'BEGIN:DAYLIGHT':
            case 'BEGIN:VTIMEZONE':
            case 'BEGIN:STANDARD':
              $component = $value;
              break;

            case 'END:VEVENT':
            case 'END:VCALENDAR':
            case 'END:DAYLIGHT':
            case 'END:VTIMEZONE':
            case 'END:STANDARD':
              $component = 'VCALENDAR';
              break;

            default:
              $this->addCalendarComponentWithKeyAndValue($component, $keyword, $value);
          }
        }
      }
    }
  }

  /**
   * Add to $this->ical array one value and key.
   *
   * @param string $component
   *   This could be VTODO, VEVENT, VCALENDAR, ...
   * @param string $keyword
   *   The keyword, for example DTSTART.
   * @param string $value
   *   The value, for example 20110105T090000Z.
   */
  private function addCalendarComponentWithKeyAndValue($component, $keyword, $value) {
    if ($keyword == FALSE) {
      $keyword = $this->lastKeyword;
    }
    switch ($component) {
      case 'VEVENT':
        if (!isset($this->cal[$component][$this->eventCount - 1][$keyword . '_array'])) {
          // Create array().
          $this->cal[$component][$this->eventCount - 1][$keyword . '_array'] = array();
        }
        if (is_array($value)) {
          // Add array of properties to the end.
          array_push($this->cal[$component][$this->eventCount - 1][$keyword . '_array'], $value);
        }
        else {
          if (!isset($this->cal[$component][$this->eventCount - 1][$keyword])) {
            $this->cal[$component][$this->eventCount - 1][$keyword] = $value;
          }
          $this->cal[$component][$this->eventCount - 1][$keyword . '_array'][] = $value;
          // Glue back together for multi-line content.
          if ($this->cal[$component][$this->eventCount - 1][$keyword] != $value) {
            // First char.
            $ord = (isset($value[0])) ? ord($value[0]) : NULL;

            // Is space or tab?.
            if (in_array($ord, array(9, 32))) {
              // Only trim the first character.
              $value = substr($value, 1);
            }
            // Account for multiple definitions of cur. keyword (e.g. ATTENDEE).
            if (is_array($this->cal[$component][$this->eventCount - 1][$keyword . '_array'][1])) {
              // Concat value *with separator* as content spans multple lines.
              $this->cal[$component][$this->eventCount - 1][$keyword] .= ';' . $value;
            }
            else {
              if ($keyword === 'EXDATE') {
                // This will give out a comma separated EXDATE string
                // as per RFC2445.
                // Example:
                // EXDATE:19960402T010000Z,19960403T010000Z,19960404T010000Z.
                // Usage: $event['EXDATE'] will print out
                // 19960402T010000Z,19960403T010000Z,19960404T010000Z.
                $this->cal[$component][$this->eventCount - 1][$keyword] .= ',' . $value;
              }
              else {
                // Concat value as content spans multiple lines.
                $this->cal[$component][$this->eventCount - 1][$keyword] .= $value;
              }
            }
          }
        }
        break;

      default:
        $this->cal[$component][$keyword] = $value;
    }
    $this->lastKeyword = $keyword;
  }

  /**
   * Get a key-value pair of a string.
   *
   * @param string $text
   *   Which is like "VCALENDAR:Begin" or "LOCATION:".
   *
   * @return array
   *   array("VCALENDAR", "Begin").
   */
  private function keyValueFromString($text) {
    // Match colon separator outside of quoted substrings.
    // Fallback to nearest semicolon outside of quoted substrings,
    // if colon cannot be found.
    // Do not try and match within the value paired with the keyword.
    preg_match('/(.*?)(?::(?=(?:[^"]*"[^"]*")*[^"]*$)|;(?=[^:]*$))([\w\W]*)/', htmlspecialchars($text, ENT_QUOTES, 'UTF-8'), $matches);
    if (count($matches) == 0) {
      return FALSE;
    }
    if (preg_match('/^([A-Z-]+)([;][\w\W]*)?$/', $matches[1])) {
      // Remove first match and re-align ordering.
      $matches = array_splice($matches, 1, 2);
      // Process properties.
      if (preg_match('/([A-Z-]+)[;]([\w\W]*)/', $matches[0], $properties)) {
        // Remove first match.
        array_shift($properties);
        // Fix to ignore everything in keyword after a ;
        // (e.g. Language, TZID, etc.).
        $matches[0] = $properties[0];
        // Repeat removing first match.
        array_shift($properties);
        $formatted = array();
        foreach ($properties as $property) {
          // Match semicolon separator outside of quoted substrings.
          preg_match_all('~[^\r\n";]+(?:"[^"\\\]*(?:\\\.[^"\\\]*)*"[^\r\n";]*)*~', $property, $attributes);
          // Remove multi-dimensional array and use the first key.
          $attributes = (count($attributes) == 0) ? array($property) : reset($attributes);
          foreach ($attributes as $attribute) {
            // Match equals sign separator outside of quoted substrings.
            preg_match_all('~[^\r\n"=]+(?:"[^"\\\]*(?:\\\.[^"\\\]*)*"[^\r\n"=]*)*~', $attribute, $values);
            // Remove multi-dimensional array and use the first key.
            $value = (count($values) == 0) ? NULL : reset($values);
            if (is_array($value) && isset($value[1])) {
              // Remove double quotes from beginning and end only.
              $formatted[$value[0]] = trim($value[1], '"');
            }
          }
        }
        // Assign the keyword property information.
        $properties[0] = $formatted;
        // Add match to beginning of array.
        array_unshift($properties, $matches[1]);
        $matches[1] = $properties;
      }
      return $matches;
    }
    else {
      // Ignore this match.
      return FALSE;
    }
  }

}
