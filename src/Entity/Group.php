<?php

namespace Drupal\effective_activism\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Group entity.
 *
 * @ingroup effective_activism
 *
 * @ContentEntityType(
 *   id = "group",
 *   label = @Translation("Group"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\effective_activism\ListBuilder\GroupListBuilder",
 *     "form" = {
 *       "default" = "Drupal\effective_activism\Form\GroupForm",
 *       "add" = "Drupal\effective_activism\Form\GroupForm",
 *       "edit" = "Drupal\effective_activism\Form\GroupForm",
 *       "publish" = "Drupal\effective_activism\Form\GroupPublishForm",
 *     },
 *     "access" = "Drupal\effective_activism\AccessControlHandler\GroupAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\effective_activism\RouteProvider\GroupHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "groups",
 *   revision_table = "groups_revision",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/o/{organization}/g/{group}",
 *     "add-form" = "/o/{organization}/g/add",
 *     "edit-form" = "/o/{organization}/g/{group}/edit",
 *     "events" = "/o/{organization}/g/{group}/e",
 *     "imports" = "/o/{organization}/g/{group}/imports",
 *     "publish-form" = "/o/{organization}/g/{group}/publish",
 *   },
 * )
 */
class Group extends RevisionableContentEntityBase implements GroupInterface {

  use EntityChangedTrait;

  const THEME_ID = self::class;

  const WEIGHTS = [
    'user_id',
    'organization',
    'title',
    'logo',
    'description',
    'website',
    'phone_number',
    'email_address',
    'location',
    'timezone',
    'organizers',
  ];

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
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
  public function getSiblings() {
    $result = \Drupal::entityQuery('group')
      ->condition('organization', $this->get('organization')->entity->id())
      ->execute();
    return Group::loadMultiple($result);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the group.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\effective_activism\Helper\AccountHelper::getCurrentUserId')
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
      ]);
    $fields['organization'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Organization'))
      ->setDescription(t('The organization of the group.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'organization')
      ->setSetting('handler', 'default')
      ->setCardinality(1)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => array_search('organization', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => array_search('organization', self::WEIGHTS),
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ]);
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the group.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 50,
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
    $fields['logo'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Logo'))
      ->setDescription(t('Upload a logo for the group.'))
      ->setSettings([
        'file_directory' => 'logo',
        'alt_field_required' => FALSE,
        'file_extensions' => 'png jpg jpeg',
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'default',
        'weight' => array_search('logo', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'label' => 'hidden',
        'type' => 'image_image',
        'weight' => array_search('logo', self::WEIGHTS),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['website'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Website'))
      ->setDescription(t('The website of the group.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => array_search('website', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => array_search('website', self::WEIGHTS),
        'settings' => [
          'placeholder' => t('Website'),
        ],
      ]);
    $fields['phone_number'] = BaseFieldDefinition::create('telephone')
      ->setLabel(t('Phone number'))
      ->setDescription(t('The phone number of the group.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => array_search('phone_number', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'telephone_default',
        'weight' => array_search('phone_number', self::WEIGHTS),
        'settings' => [
          'placeholder' => t('Phone number'),
        ],
      ]);
    $fields['email_address'] = BaseFieldDefinition::create('email')
      ->setLabel(t('E-mail address'))
      ->setDescription(t('The e-mail address of the group.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => array_search('email_address', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => array_search('email_address', self::WEIGHTS),
        'settings' => [
          'placeholder' => t('E-mail address'),
        ],
      ]);
    $fields['location'] = BaseFieldDefinition::create('location')
      ->setLabel(t('Location'))
      ->setDescription(t('The location of the group.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
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
    $fields['timezone'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Timezone'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDefaultValue('inherit')
      ->setSettings([
        'allowed_values' => array_merge(['inherit' => 'Inherit from organization'], system_time_zones()),
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => array_search('timezone', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => array_search('timezone', self::WEIGHTS),
      ]);
    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setDescription(t('The description of the group.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => array_search('description', self::WEIGHTS),
        'settings' => [
          'rows' => 6,
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'basic_string',
        'weight' => array_search('description', self::WEIGHTS),
        'settings' => [
          'placeholder' => t('Description'),
        ],
      ]);
    $fields['organizers'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Organizers'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => array_search('organizers', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'inline_organizer_invitation',
        'settings' => [
          'allow_new' => FALSE,
          'allow_existing' => TRUE,
        ],
        'weight' => array_search('organizers', self::WEIGHTS),
      ]);
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the group is published.'))
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
