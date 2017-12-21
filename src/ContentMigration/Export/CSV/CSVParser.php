<?php

namespace Drupal\effective_activism\ContentMigration\Export\CSV;

use Drupal;
use Drupal\Core\Entity\EntityInterface;
use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Entity\Export;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Helper\GroupHelper;
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
    'field_latitude',
    'field_longitude',
    'field_timestamp',
    'import',
    'langcode',
    'location',
    'organization',
    'parent',
    'revision_created',
    'revision_log_message',
    'revision_id',
    'revision_user',
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
   * Group.
   *
   * @var \Drupal\effective_activism\Entity\Group
   */
  private $group;

  /**
   * Export.
   *
   * @var \Drupal\effective_activism\Entity\Export
   */
  private $export;

  /**
   * Creates the CSVParser Object.
   *
   * @param \Drupal\effective_activism\Entity\Group $group
   *   The group to export events from.
   * @param \Drupal\effective_activism\Entity\Export $export
   *   The export to save the file to.
   */
  public function __construct(Group $group, Export $export) {
    $this->group = $group;
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
    $this->itemCount = GroupHelper::getEvents($this->group, 0, 0, FALSE);
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
    $event = Event::load($event);
    $row = [];
    foreach ($event->toArray() as $field_name => $data) {
      if (!in_array($field_name, self::FIELDS_BLACKLIST)) {
        foreach ($data as $delta => $properties) {
          foreach ($properties as $key => $value) {
            switch ($key) {
              case 'value':
                $row[$field_name] = $value;
                break;

              case 'target_id':
                $referenced_entity = $this->unpackEntityReference($event, $field_name, $delta);
                $row[key($referenced_entity)][] = current($referenced_entity);
                break;
            }
          }
        }
      }
      // Special handling of addresses.
      if ($field_name === 'location') {
        $row['address'] = $data[0]['address'];
        $row['address_extra_information'] = $data[0]['extra_information'];
        $row['latitude'] = $data[0]['latitude'];
        $row['longitude'] = $data[0]['longitude'];
      }
    }
    // Convert values to CSV-formatted strings.
    foreach ($row as $key => $value) {
      if (is_array($value)) {
        foreach ($value as $entityArray) {
          unset($row[$key]);
          // This will overwrite preceding entity references of the same type.
          $row = array_merge($row, $this->collapseEntityArray($key, $entityArray));
        }
      }
      else {
        $row[$key] = $this->formatValue($value);
      }
    }
    return $row;
  }

  /**
   * Unpacks an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $parent_entity
   *   The parent entity.
   * @param string $parent_field_name
   *   The field name of the parent entity reference field.
   * @param int $field_delta
   *   The delta of the field.
   *
   * @return string
   *   An array of the entity fields, with the entity bundle id as key.
   */
  private function unpackEntityReference(EntityInterface $parent_entity, $parent_field_name, $field_delta = 0) {
    // Set entity type/import name.
    $bundle_entity_type = $parent_entity->get($parent_field_name)->get($field_delta)->entity->getEntityType()->getBundleEntityType();
    $bundle_id = $parent_entity->get($parent_field_name)->get($field_delta)->entity->bundle();
    $bundle = Drupal::entityTypeManager()->getStorage($bundle_entity_type)->load($bundle_id);
    if ($bundle && $bundle->get('importname') !== NULL) {
      $entity_identifier = $bundle->get('importname');
    }
    else {
      $entity_identifier = $parent_entity->get($parent_field_name)->get($field_delta)->entity->bundle();
    }
    // Iterate entity fields.
    foreach ($parent_entity->get($parent_field_name)->get($field_delta)->entity->toArray() as $field_name => $data) {
      if (!in_array($field_name, self::FIELDS_BLACKLIST)) {
        foreach ($data as $delta) {
          foreach ($delta as $key => $value) {
            switch ($key) {
              case 'value':
                $pieces[$field_name] = $value;
                break;

              case 'target_id':
                $pieces[$field_name] = $this->unpackEntityReference($parent_entity->get($parent_field_name)->get($field_delta)->entity, $field_name);
                break;
            }
          }
        }
      }
    }
    return [
      $entity_identifier => $pieces,
    ];
  }

  /**
   * Recusively collapses an entity array to a CSV-formatted string.
   *
   * @param string $entity_bundle_id
   *   The entity array bundle.
   * @param array $array
   *   The entity array to collapse.
   *
   * @return array
   *   Array with entity bundle id as key and CSV-formatted string as value.
   */
  private function collapseEntityArray($entity_bundle_id, array $array) {
    $row = [];
    foreach ($array as $field_name => $value) {
      if (is_array($value)) {
        $row = array_merge($row, $this->collapseEntityArray(sprintf('%s_%s', $entity_bundle_id, $field_name), $value));
      }
      else {
        $row[sprintf('%s_%s', $entity_bundle_id, $field_name)] = self::formatValue($value);
      }
    }
    return $row;
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
    return strpos($value, ',') !== FALSE ? sprintf('"%s"', str_replace('"', '""', $value)) : str_replace('"', '""', $value);
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
    // Force some column names to be first.
    $headers = [
      'title',
    ];
    foreach ($rows as $row) {
      $keys = array_keys($row);
      $headers = array_unique(array_merge($headers, $keys));
    }
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
