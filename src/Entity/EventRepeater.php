<?php

namespace Drupal\effective_activism\Entity;

use DateInterval;
use DatePeriod;
use DateTime;
use Drupal;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\effective_activism\Entity\Event;
use Drupal\effective_activism\Helper\DateHelper;
use Drupal\user\UserInterface;

/**
 * Defines the EventRepeater entity.
 *
 * @ingroup effective_activism
 *
 * @ContentEntityType(
 *   id = "event_repeater",
 *   label = @Translation("Event repeater"),
 *   handlers = {
 *     "access" = "Drupal\effective_activism\AccessControlHandler\EventRepeaterAccessControlHandler",
 *   },
 *   base_table = "event_repeater",
 *   revision_table = "event_repeater_revision",
 *   revision_data_table = "event_repeater_field_revision",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 * )
 */
class EventRepeater extends RevisionableContentEntityBase implements EventRepeaterInterface {

  use EntityChangedTrait;

  const WEIGHTS = [
    'name',
    'step',
    'frequency',
    'end_on_date',
    'user_id',
  ];

  /*
   * How many events into the future should be created.
   */
  const MAX_REPEATS = 5;

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (
      $this->step->isEmpty() ||
      $this->step->value === '0'
    ) ? FALSE : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getUpcomingEvents(DrupalDateTime $now) {
    $query = Drupal::entityQuery('event');
    $query
      ->sort('start_date', 'ASC')
      ->condition('event_repeater', $this->id())
      ->condition('start_date', $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT), '>=');
    return array_values($query->execute());
  }

  /**
   * {@inheritdoc}
   */
  public function scheduleUpcomingEvents(DrupalDateTime $now) {
    $new_events_created = 0;
    $existing_events_updated = 0;
    $events_deleted = 0;
    // Get all events.
    $event_ids = $this->getUpcomingEvents($now);
    $first_event = isset($event_ids[0]) ? Event::load($event_ids[0]) : NULL;
    // Update events for this event repeater.
    if ($this->isEnabled() && isset($first_event)) {
      // Create periods from start date, step and frequency.
      $start_date = new DateTime($first_event->start_date->value);
      $difference = $start_date->diff(new DateTime($first_event->end_date->value));
      $interval = new DateInterval(sprintf('P%d%s', $this->step->value, $this->frequency->value));
      $end = $this->end_on_date->isEmpty() ? self::MAX_REPEATS : new DateTime($this->end_on_date->value);
      $date_period = new DatePeriod($start_date, $interval, $end);
      // Map periods onto existing events and create new events until
      // MAX_REPEATS is reached.
      $i = 1;
      foreach ($date_period as $new_start_date) {
        $new_end_date = clone $new_start_date;
        $new_end_date->add($difference);
        if ($i > self::MAX_REPEATS) {
          break;
        }
        if (!empty($event_ids)) {
          $event = Event::load(array_shift($event_ids));
          if (!(
            $event->start_date->value === $new_start_date->format('Y-m-d\TH:i:s') &&
            $event->end_date->value === $new_end_date->format('Y-m-d\TH:i:s')
          )) {
            $event->start_date->setValue($new_start_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));
            $event->end_date->setValue($new_end_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));
            $event->setNewRevision();
            $event->save();
            $existing_events_updated++;
          }
        }
        // Create a new event.
        else {
          $event = $first_event->createDuplicate();
          $event->start_date->setValue($new_start_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));
          $event->end_date->setValue($new_end_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));
          // Do not include results.
          $event->results->setValue(NULL);
          $event->save();
          $new_events_created++;
        }
        $i++;
      }
    }
    // Delete remaining future events.
    // This happens if 'end on' date stops before MAX_REPEATS events or event
    // repeater has been deactivated.
    if (!empty($event_ids)) {
      foreach ($event_ids as $event_id) {
        $event = Event::load($event_id);
        $start_date = new DateTime($event->start_date->value);
        if (
          $start_date->format('U') > $now->format('U') &&
          $first_event->id() !== $event->id()
        ) {
          $event->delete();
          $events_deleted++;
        }
      }
    }
    // Add logging and messages.
    if ($new_events_created > 0) {
      Drupal::logger('event_repeater')->info(sprintf('%d events created',
        $new_events_created
      ));
    }
    if ($existing_events_updated > 0) {
      Drupal::logger('event_repeater')->info(sprintf('%d events updated',
        $existing_events_updated
      ));
    }
    if ($events_deleted > 0) {
      Drupal::logger('event_repeater')->info(sprintf('%d events deleted',
        $events_deleted
      ));
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);
      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }
    // If no revision author has been set explicitly, make the EventRepeater owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['step'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Distance of repeating occurences'))
      ->setDescription(t('Type in the distance between repeating occurences.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'min' => 0,
        'max' => 99,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => array_search('step', self::WEIGHTS),
        'settings' => [
          'placeholder' => t('Repeats'),
        ],
      ])
      ->setDisplayOptions('form', [
        'weight' => array_search('step', self::WEIGHTS),
      ]);
    $fields['frequency'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Frequency'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setSettings([
        'allowed_values' => [
          'D' => 'Day',
          'W' => 'Week',
          'M' => 'Month',
          'Y' => 'Year',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => array_search('frequency', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => array_search('frequency', self::WEIGHTS),
      ]);
    $fields['end_on_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('End date'))
      ->setDescription(t('The date to end repeating events.'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setSettings([
        'default_value' => '',
        'text_processing' => 0,
      ])
      ->setDefaultValue([
        0 => NULL,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'datetime_default',
        'settings' => [
          'format_type' => 'iso_8601',
        ],
        'weight' => array_search('end_on_date', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetimepicker_widget',
        'weight' => array_search('end_on_date', self::WEIGHTS),
      ]);
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the event repeater.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => array_search('user_id', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => array_search('user_id', self::WEIGHTS),
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the event repeater is published.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE);
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));
    return $fields;
  }

}
