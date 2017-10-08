<?php

namespace Drupal\effective_activism\ContentMigration\Export;

use Drupal;
use Drupal\Core\Entity\EntityInterface;
use Drupal\effective_activism\Entity\EventInterface;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Helper\GroupHelper;
use Drupal\effective_activism\ContentMigration\ParserInterface;
use Drupal\effective_activism\ContentMigration\ParserValidationException;

/**
 * Parses entities to CSV.
 */
class CSVParser implements ParserInterface {

  const BATCHSIZE = 10;

  const CSVHEADERFORMAT = [
    'start_date',
    'end_date',
    'address',
    'address_extra_information',
    'latitude',
    'longitude',
    'title',
    'description',
    'results',
    'third_party_content',
  ];

  const FIELDS_BLACKLIST = [
    'id',
    'changed',
    'created',
    'default_langcode',
    'external_uid',
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
   * CSV filepath.
   *
   * @var string
   */
  private $filePath;

  /**
   * CSV file.
   *
   * @var resource
   */
  private $fileHandle;

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
   * The current row number.
   *
   * @var int
   */
  private $row = 0;

  /**
   * The current column number.
   *
   * @var int
   */
  private $column = 0;

  /**
   * Tracks the latest read result.
   *
   * @var Result
   */
  private $latestResult;

  /**
   * Tracks the latest read third-party content.
   *
   * @var Result
   */
  private $latestThirdPartyContent;

  /**
   * Any validation error message.
   *
   * @var array
   */
  private $errorMessage;

  /**
   * Creates the CSVParser Object.
   *
   * @param \Drupal\effective_activism\Entity\Group $group
   *   The group to export events from.
   */
  public function __construct(Group $group) {
    $this->group = $group;
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
    return $this->errorMessage;
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
        $row[$field_name] = NULL;
        if (isset($data[0])) {
          foreach ($data as $delta => $properties) {
            foreach ($properties as $key => $value) {
              switch ($key) {
                case 'value':
                  $row[$field_name] = $value;
                  break;

                case 'target_id':
                  $row[$field_name][] = $this->unpackEntityReference($event, $field_name);
                  break;
              }
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
    // Sort array by CSV header format.
    $row = array_merge(array_flip(self::CSVHEADERFORMAT), $row);
    foreach ($row as &$cell) {
      if (is_array($cell)) {
        $cell = json_encode($cell);
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
   *
   * @return string
   *   A CSV cell-friendly representation of the entity.
   */
  private function unpackEntityReference(EntityInterface $parent_entity, $parent_field_name) {
    // Set entity type/import name.
    $bundle_entity_type = $parent_entity->get($parent_field_name)->entity->getEntityType()->getBundleEntityType();
    $bundle_id = $parent_entity->get($parent_field_name)->entity->bundle();
    $bundle = Drupal::entityTypeManager()->getStorage($bundle_entity_type)->load($bundle_id);
    if ($bundle && $bundle->get('importname') !== NULL) {
      $pieces['type'] = $bundle->get('importname');
    } else {
      $pieces['type'] = $parent_entity->get($parent_field_name)->entity->bundle();
    }
    // Iterate entity fields.
    foreach ($parent_entity->get($parent_field_name)->entity->toArray() as $field_name => $data) {
      if (!in_array($field_name, self::FIELDS_BLACKLIST)) {
        if (isset($data[0])) {
          foreach ($data[0] as $key => $value) {
            switch ($key) {
              case 'value':
                $pieces[$field_name] = $value;
                break;

              case 'target_id':
                $pieces[$field_name] = $this->unpackEntityReference($parent_entity->get($parent_field_name)->entity, $field_name);
                break;
            }
          }
        }
      }
    }
    return $pieces;
  }

}
