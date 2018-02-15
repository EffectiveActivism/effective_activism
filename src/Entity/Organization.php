<?php

namespace Drupal\effective_activism\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\user\UserInterface;
use Drupal\effective_activism\Constant;

/**
 * Defines the Organization entity.
 *
 * @ingroup effective_activism
 *
 * @ContentEntityType(
 *   id = "organization",
 *   label = @Translation("Organization"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\effective_activism\ListBuilder\OrganizationListBuilder",
 *     "form" = {
 *       "default" = "Drupal\effective_activism\Form\OrganizationForm",
 *       "add" = "Drupal\effective_activism\Form\OrganizationForm",
 *       "edit" = "Drupal\effective_activism\Form\OrganizationForm",
 *       "publish" = "Drupal\effective_activism\Form\OrganizationPublishForm",
 *     },
 *     "access" = "Drupal\effective_activism\AccessControlHandler\OrganizationAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\effective_activism\RouteProvider\OrganizationHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "organizations",
 *   revision_table = "organizations_revision",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *  links = {
 *     "canonical" = "/manage/organizations/{organization}",
 *     "add-form" = "/manage/organizations/add",
 *     "edit-form" = "/manage/organizations/{organization}/edit",
 *     "publish-form" = "/manage/organizations/{organization}/publish",
 *     "collection" = "/manage/organizations",
 *     "groups" = "/manage/organizations/{organization}/groups",
 *     "event_templates" = "/manage/organizations/{organization}/event-templates",
 *     "exports" = "/manage/organizations/{organization}/exports",
 *     "filters" = "/manage/organizations/{organization}/filters",
 *     "results" = "/manage/organizations/{organization}/results",
 *   },
 * )
 */
class Organization extends RevisionableContentEntityBase implements OrganizationInterface {

  use EntityChangedTrait;

  const WEIGHTS = [
    'user_id',
    'title',
    'logo',
    'description',
    'website',
    'phone_number',
    'email_address',
    'location',
    'timezone',
    'managers',
    'event_creation',
  ];

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
      'managers' => \Drupal::currentUser()->id(),
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
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the organization.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
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
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the organization.'))
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
      ->setDescription(t('Upload a logo for the organization.'))
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
      ]);
    $fields['website'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Website'))
      ->setDescription(t('The website of the organization.'))
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
      ->setDescription(t('The phone number of the organization.'))
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
      ->setDescription(t('The e-mail address of the organization.'))
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
      ->setDescription(t('The location of the organization.'))
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
      ->setSettings([
        'allowed_values' => system_time_zones(),
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
      ->setDescription(t('The description of the organization.'))
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
    $fields['managers'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Managers'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => array_search('managers', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'inline_manager_invitation',
        'settings' => [
          'allow_new' => FALSE,
          'allow_existing' => TRUE,
        ],
        'weight' => array_search('managers', self::WEIGHTS),
      ]);
    $fields['event_creation'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Event creation'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setSettings([
        'allowed_values' => [
          Constant::EVENT_CREATION_ALL => t('Show all event creation links'),
          Constant::EVENT_CREATION_EVENT => t('Show only \'Create event\' links'),
          Constant::EVENT_CREATION_EVENT_TEMPLATE => t('Show only \'Create event from template \' links'),
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => array_search('event_creation', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => array_search('event_creation', self::WEIGHTS),
      ]);
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the organization is published.'))
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
