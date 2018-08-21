<?php

namespace Drupal\effective_activism\ContentMigration\Export\CSV;

use Drupal;
use Drupal\effective_activism\Entity\Export;
use Drupal\effective_activism\Entity\Filter;
use Drupal\effective_activism\Helper\FilterHelper;
use Drupal\effective_activism\ContentMigration\ParserInterface;

/**
 * Parses entities to CSV.
 */
class CSVParser implements ParserInterface {

  const BATCHSIZE = 10;

  const FIELDS_BLACKLIST = [
    'id',
    'changed',
    'created',
    'default_langcode',
    'external_uid',
    'field_timestamp',
    'import',
    'langcode',
    'organization',
    'revision_created',
    'revision_log_message',
    'revision_id',
    'revision_user',
    'revision_default',
    'status',
    'tid',
    'type',
    'vid',
    'weight',
    'user_id',
    'uid',
    'uuid',
  ];

  /**
   * Item count.
   *
   * @var int
   */
  private $itemCount;

  /**
   * Filter.
   *
   * @var \Drupal\effective_activism\Entity\Filter
   */
  private $filter;

  /**
   * Export.
   *
   * @var \Drupal\effective_activism\Entity\Export
   */
  private $export;

  /**
   * Creates the CSVParser Object.
   *
   * @param \Drupal\effective_activism\Entity\Filter $filter
   *   The filter to export events with.
   * @param \Drupal\effective_activism\Entity\Export $export
   *   The export to save the file to.
   */
  public function __construct(Filter $filter, Export $export) {
    $this->filter = $filter;
    $this->export = $export;
    $this->setItemCount();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getErrorMessage() {
    return NULL;
  }

  /**
   * Return the export entity.
   *
   * @return \Drupal\effective_activism\Entity\Export
   *   The export entity.
   */
  public function getExportEntity() {
    return $this->export;
  }

  /**
   * Set the number of items to be exported.
   */
  private function setItemCount() {
    // Check if export is of group events or entire organization.
    if ($this->export->parent->isEmpty()) {
      $this->itemCount = FilterHelper::getEvents($this->filter, 0, 0, FALSE);
    }
    else {
      $group = $this->export->parent->first()->get('entity')->getTarget()->getValue();
      $this->itemCount = FilterHelper::getEventsByGroup($this->filter, $group, 0, 0, FALSE);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemCount() {
    return count($this->itemCount);
  }

  /**
   * {@inheritdoc}
   */
  public function getNextBatch($position) {
    return array_slice($this->itemCount, $position, self::BATCHSIZE);
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($event) {
    $row = $this->unpackEntity($event, 'event', [], array_map(function ($element) {
      return $element['value'];
    }, $this->export->columns_event->getValue()));
    foreach ($row as $key => $value) {
      if (is_array($value)) {
        $value = $this->collapseArray($value);
      }
      $row[$this->formatValue($key)] = $this->formatValue($value);
    }
    return $row;
  }

  /**
   * Collapses an array to a single value.
   *
   * @param array $array
   *   The array to collapse.
   *
   * @return string
   *   The formatted value.
   */
  private function collapseArray(array $array) {
    $formatted_value = NULL;
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        $value = $this->collapseArray($value);
      }
      if ($formatted_value === NULL) {
        $formatted_value = $value;
      }
      else {
        if (is_numeric($value)) {
          $formatted_value += $value;
        }
        else {
          if (!in_array($value, array_map(function ($value) {
            return trim($value);
          }, explode(',', $formatted_value)))) {
            $formatted_value .= ', ' . $value;
          }
        }
      }
    }
    return $formatted_value;
  }

  /**
   * Format a string to CSV.
   *
   * @param string $value
   *   A string to format.
   *
   * @return string
   *   A CSV-formatted string.
   */
  private function formatValue($value) {
    return (strpos($value, ',') !== FALSE || strpos($value, ';') !== FALSE) ? sprintf('"%s"', str_replace('"', '""', $value)) : $value;
  }

  /**
   * Unpack entity.
   *
   * @param string $entity_id
   *   The entity id to unpack.
   * @param string $entity_type
   *   The entity type to unpack.
   * @param array $ignore_fields
   *   Additional fields to ignore on top of the field blacklist.
   * @param array $whitelist
   *   Whitelisted fields.
   *
   * @return array
   *   A row containing the entity data.
   */
  private function unpackEntity($entity_id, $entity_type, array $ignore_fields = [], array $whitelist = []) {
    $entity = Drupal::entityTypeManager()
      ->getStorage($entity_type)
      ->load($entity_id);
    $row = [];
    foreach ($entity->toArray() as $field_name => $data) {
      // Skip fields that aren't whitelisted (if set).
      if (!empty($whitelist) && !in_array($field_name, $whitelist)) {
        continue;
      }
      if (!in_array($field_name, array_merge(self::FIELDS_BLACKLIST, $ignore_fields))) {
        $label = (string) $entity->get($field_name)->getFieldDefinition()->getLabel();
        // Temporary fix to remove duplicate entity references.
        // Todo: AFA-242 Remove duplicate entity reference of third-party
        // content.
        if (isset($data[0]['target_id'])) {
          $data = array_unique($data, SORT_REGULAR);
        }
        foreach ($data as $delta => $properties) {
          foreach ($properties as $key => $value) {
            if (!empty($value)) {
              switch ($key) {
                case 'value':
                  $row[$label] = $value;
                  break;

                case 'address':
                  $row['Address'] = $value;
                  break;

                case 'extra_information':
                  $row['Address extra information'] = $value;
                  break;

                case 'latitude':
                  $row['Latitude'] = $value;
                  break;

                case 'longitude':
                  $row['Longitude'] = $value;
                  break;

                case 'target_id':
                  $referenced_entity_bundle_type = $entity->get($field_name)->get($delta)->entity->getEntityType()->getBundleEntityType();
                  // If the referenced entity does not have a bundle, we do not
                  // iterate its content.
                  if ($referenced_entity_bundle_type === NULL) {
                    $row[$label][] = (string) $entity->get($field_name)->get($delta)->entity->label();
                  }
                  else {
                    $referenced_entity_type = $entity->get($field_name)->get($delta)->entity->getEntityType()->id();
                    $referenced_entity_bundle_id = $entity->get($field_name)->get($delta)->entity->bundle();
                    $referenced_bundle = Drupal::entityTypeManager()->getStorage($referenced_entity_bundle_type)->load($referenced_entity_bundle_id);
                    // Append the entity type label, except for Data and term
                    // entities.
                    if (!in_array((string) $entity->get($field_name)->get($delta)->entity->getEntityType()->getLabel(), [
                      'Data',
                      'Taxonomy term',
                    ])) {
                      $referenced_entity_label = sprintf(
                        '%s: %s',
                        $entity->get($field_name)->get($delta)->entity->getEntityType()->getLabel(),
                        $referenced_bundle->label()
                      );
                    }
                    else {
                      $referenced_entity_label = $referenced_bundle->label();
                    }
                    // Add field whitelist to result type.
                    $embedded_whitelist = [];
                    if ($referenced_entity_type === 'result') {
                      $embedded_whitelist = array_map(function ($element) {
                        return $element['value'];
                      }, $this->export->columns_result->getValue());
                    }
                    // Add field whitelist to third_party_content type.
                    elseif ($referenced_entity_type === 'third_party_content') {
                      $embedded_whitelist = array_map(function ($element) {
                        return $element['value'];
                      }, $this->export->columns_third_party_content->getValue());
                    }
                    // Iterate referenced entity and exclude certain fields.
                    $referenced_entity = $this->unpackEntity($value, $referenced_entity_type, [
                      'field_latitude',
                      'field_longitude',
                    ], $embedded_whitelist);
                    foreach ($referenced_entity as $referenced_field_label => $referenced_field_value) {
                      if ($referenced_entity_label === $referenced_field_label) {
                        $row[$referenced_entity_label][] = $referenced_field_value;
                      }
                      else {
                        $row[sprintf('%s - %s', $referenced_entity_label, $referenced_field_label)][] = $referenced_field_value;
                      }
                    }
                  }
                  break;
              }
            }
          }
        }
      }
    }
    return $row;
  }

  /**
   * Calculate headers.
   *
   * @param array $rows
   *   A row of CSV formatted strings with header keys.
   *
   * @return array
   *   A header row containing all header keys.
   */
  public static function buildHeaders(array $rows) {
    // Force some columns to be first.
    $order = [
      'Group',
      'Start date',
      'End date',
      'Address',
      'Address extra information',
      'Latitude',
      'Longitude',
      'Title',
      'Description',
      'Event template',
    ];
    $headers = [];
    foreach ($rows as $row) {
      $keys = array_keys($row);
      $headers = array_unique(array_merge($headers, $keys));
    }
    sort($headers);
    // Remove columns from column order that isn't already present in headers.
    foreach ($order as $key => $value) {
      if (!in_array($value, $headers)) {
        unset($order[$key]);
      }
    }
    $headers = array_unique(array_merge($order, $headers));
    return $headers;
  }

  /**
   * Convert array to a CSV-formatted string.
   *
   * @param array $rows
   *   A row of CSV-formatted strings.
   * @param array $headers
   *   An array of header values to include and sort by.
   *
   * @return string
   *   A CSV-formatted string.
   */
  public static function convert(array $rows, array $headers) {
    $csv = sprintf('%s%s', implode(',', $headers), PHP_EOL);
    // Build row order from headers.
    foreach ($rows as $row) {
      // Sort row by headers.
      $csv_row = [];
      foreach ($headers as $header) {
        if (isset($row[$header])) {
          $csv_row[] = $row[$header];
        }
        else {
          $csv_row[] = NULL;
        }
      }
      $csv .= sprintf('%s%s', trim(preg_replace('/\s+/', ' ', implode(',', $csv_row))), PHP_EOL);
    }
    return $csv;
  }

}
