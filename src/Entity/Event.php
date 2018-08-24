<?php

namespace Drupal\effective_activism\Entity;

use Drupal;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\link\Plugin\Field\FieldType\LinkItem;
use Drupal\user\UserInterface;

/**
 * Defines the Event entity.
 *
 * @ingroup effective_activism
 *
 * @ContentEntityType(
 *   id = "event",
 *   label = @Translation("Event"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\effective_activism\ListBuilder\EventListBuilder",
 *     "form" = {
 *       "default" = "Drupal\effective_activism\Form\EventForm",
 *       "add" = "Drupal\effective_activism\Form\EventForm",
 *       "add-from-template" = "Drupal\effective_activism\Form\EventTemplateSelectionForm",
 *       "delete" = "Drupal\effective_activism\Form\EventDeleteForm",
 *       "edit" = "Drupal\effective_activism\Form\EventForm",
 *       "publish" = "Drupal\effective_activism\Form\EventPublishForm",
 *       "repeat" = "Drupal\effective_acitivism\Form\EventRepeaterForm",
 *     },
 *     "access" = "Drupal\effective_activism\AccessControlHandler\EventAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\effective_activism\RouteProvider\EventHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "events",
 *   revision_table = "events_revision",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/o/{organization}/g/{group}/e/{event}",
 *     "add-form" = "/o/{organization}/g/{group}/e/add",
 *     "add-from-template" = "/o/{organization}/g/{group}/e/add-from-template",
 *     "add-from-template-form" = "/o/{organization}/g/{group}/e/add/{event_template}",
 *     "delete-form" = "/o/{organization}/g/{group}/e/{event}/delete",
 *     "edit-form" = "/o/{organization}/g/{group}/e/{event}/edit",
 *     "publish-form" = "/o/{organization}/g/{group}/e/{event}/publish",
 *     "repeat-form" = "/o/{organization}/g/{group}/e/{event}/repeat",
 *   },
 * )
 */
class Event extends RevisionableContentEntityBase implements EventInterface {

  use EntityChangedTrait;

  const THEME_ID = self::class;

  const WEIGHTS = [
    'title',
    'description',
    'parent',
    'start_date',
    'end_date',
    'location',
    'link',
    'results',
    'external_uid',
    'import',
    'third_party_content',
    'event_template',
    'user_id',
  ];

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
    $this->set('status', $published ? NODE_PUBLISHED : NODE_NOT_PUBLISHED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the event.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => array_search('title', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => array_search('title', self::WEIGHTS),
        'settings' => [
          'placeholder' => t('Title'),
        ],
      ]);
    $fields['start_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Start date'))
      ->setDescription(t('The beginning of the event.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => '',
        'text_processing' => 0,
      ])
      ->setDefaultValue([
        0 => [
          'default_date_type' => 'now',
          'default_date' => 'tomorrow noon',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'datetime_default',
        'settings' => [
          'format_type' => 'iso_8601',
        ],
        'weight' => array_search('start_date', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetimepicker_widget',
        'weight' => array_search('start_date', self::WEIGHTS),
      ]);
    $fields['end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('End date'))
      ->setDescription(t('The end of the event.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => '',
        'text_processing' => 0,
      ])
      ->setDefaultValue([
        0 => [
          'default_date_type' => 'now',
          'default_date' => 'tomorrow 13:00',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'datetime_default',
        'settings' => [
          'format_type' => 'iso_8601',
        ],
        'weight' => array_search('end_date', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetimepicker_widget',
        'weight' => array_search('end_date', self::WEIGHTS),
      ]);
    $fields['location'] = BaseFieldDefinition::create('location')
      ->setLabel(t('Location'))
      ->setDescription(t('The location of the event.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'location_default',
        'weight' => array_search('location', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'location_default',
        'weight' => array_search('location', self::WEIGHTS),
      ]);
    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setDescription(t('The description of the event.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => array_search('description', self::WEIGHTS),
        'settings' => [
          'rows' => 6,
          'placeholder' => t('Description'),
        ],
      ])
      ->setDisplayOptions('view', [
        'type' => 'basic_string',
        'weight' => array_search('description', self::WEIGHTS),
      ]);
    $fields['link'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Link'))
      ->setDescription(t('An optional link to third-party sources of the event.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'link_type' => LinkItem::LINK_EXTERNAL,
        'title' => DRUPAL_DISABLED,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => array_search('link', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'link_default',
        'weight' => array_search('link', self::WEIGHTS),
      ]);
    $fields['results'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Results'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'result')
      ->setSetting('handler', 'default')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => array_search('results', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'inline_entity_form_complex',
        'settings' => [
          'allow_new' => TRUE,
          'allow_existing' => TRUE,
        ],
        'weight' => array_search('results', self::WEIGHTS),
      ]);
    $fields['parent'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Group'))
      ->setRevisionable(TRUE)
      ->setDescription(t('The group that this event belongs to.'))
      ->setSetting('target_type', 'group')
      ->setSetting('handler', 'default')
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => array_search('parent', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => array_search('parent', self::WEIGHTS),
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ]);
    $fields['external_uid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The external UID.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'region' => 'hidden',
        'weight' => array_search('external_uid', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => array_search('external_uid', self::WEIGHTS),
        'settings' => [
          'placeholder' => t('External UID'),
        ],
      ]);
    $fields['import'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Import'))
      ->setRevisionable(TRUE)
      ->setDescription(t('The import that this event belongs to.'))
      ->setSetting('target_type', 'import')
      ->setSetting('handler', 'default')
      ->setCardinality(1)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => array_search('import', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => array_search('import', self::WEIGHTS),
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ]);
    $fields['third_party_content'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Third-party content'))
      ->setRevisionable(TRUE)
      ->setDescription(t('Third-party content entities.'))
      ->setSetting('target_type', 'third_party_content')
      ->setSetting('handler', 'default')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => array_search('third_party_content', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => array_search('third_party_content', self::WEIGHTS),
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ]);
    $fields['event_template'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Event template'))
      ->setRevisionable(TRUE)
      ->setDescription(t('The event template used to create this event.'))
      ->setSetting('target_type', 'event_template')
      ->setSetting('handler', 'default')
      ->setCardinality(1)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => array_search('event_template', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => array_search('event_template', self::WEIGHTS),
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ]);
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Event entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\effective_activism\Helper\AccountHelper::getCurrentUserId')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'region' => 'hidden',
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
      ]);
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Event is published.'))
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
