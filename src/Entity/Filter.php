<?php

namespace Drupal\effective_activism\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Filter entity.
 *
 * @ingroup effective_activism
 *
 * @ContentEntityType(
 *   id = "filter",
 *   label = @Translation("Filter"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\effective_activism\ListBuilder\FilterListBuilder",
 *     "form" = {
 *       "default" = "Drupal\effective_activism\Form\FilterForm",
 *       "add" = "Drupal\effective_activism\Form\FilterForm",
 *       "edit" = "Drupal\effective_activism\Form\FilterForm",
 *       "publish" = "Drupal\effective_activism\Form\FilterPublishForm",
 *     },
 *     "access" = "Drupal\effective_activism\AccessControlHandler\FilterAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\effective_activism\RouteProvider\FilterHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "filter",
 *   revision_table = "filter_revision",
 *   revision_data_table = "filter_field_revision",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/manage/filters/{filter}",
 *     "add-form" = "/manage/filters/add",
 *     "edit-form" = "/manage/filters/{filter}/edit",
 *     "publish-form" = "/manage/filters/{filter}/publish",
 *     "version-history" = "/manage/filters/{filter}/revisions",
 *     "revision" = "/manage/filters/{filter}/revisions/{filter_revision}/view",
 *     "revision_revert" = "/manage/filters/{filter}/revisions/{filter_revision}/revert",
 *     "revision_delete" = "/manage/filters/{filter}/revisions/{filter_revision}/delete",
 *     "collection" = "/manage/filters",
 *   },
 * )
 */
class Filter extends RevisionableContentEntityBase implements FilterInterface {

  use EntityChangedTrait;

  const WEIGHTS = [
    'name',
    'organization',
    'parent',
    'start_date',
    'end_date',
    'location',
    'result_types',
    'user_id',
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
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);
      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }
    // If no revision author has been set explicitly, make the filter owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
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
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the filter.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => array_search('name', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => array_search('name', self::WEIGHTS),
        'settings' => [
          'placeholder' => t('Title'),
        ],
      ]);
    $fields['organization'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Organization'))
      ->setDescription(t('The organization of the filter.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'organization')
      ->setSetting('handler', 'default')
      ->setCardinality(1)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => array_search('organization', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'organization_selector',
        'weight' => array_search('organization', self::WEIGHTS),
      ]);
    $fields['start_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Start date'))
      ->setDescription(t('Events that start on or after this date'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setSettings([
        'default_value' => '',
        'text_processing' => 0,
        'datetime_type' => 'date',
      ])
      ->setDefaultValue([
        0 => NULL,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'datetime_default',
        'weight' => array_search('start_date', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => array_search('start_date', self::WEIGHTS),
      ]);
    $fields['end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('End date'))
      ->setDescription(t('Events that ends on or before this date'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setSettings([
        'default_value' => '',
        'text_processing' => 0,
        'datetime_type' => 'date',
      ])
      ->setDefaultValue([
        0 => NULL,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'datetime_default',
        'weight' => array_search('end_date', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => array_search('end_date', self::WEIGHTS),
      ]);
    $fields['location'] = BaseFieldDefinition::create('location')
      ->setLabel(t('Location'))
      ->setDescription(t('Events that are held at this location.'))
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
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the filter.'))
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
      ->setDescription(t('A boolean indicating whether the filter is published.'))
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
