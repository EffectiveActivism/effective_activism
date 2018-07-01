<?php

namespace Drupal\effective_activism\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the Export entity.
 *
 * @ingroup effective_activism
 *
 * @ContentEntityType(
 *   id = "export",
 *   label = @Translation("Export"),
 *   bundle_label = @Translation("Export type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\effective_activism\ListBuilder\ExportListBuilder",
 *     "form" = {
 *       "default" = "Drupal\effective_activism\Form\ExportForm",
 *       "add" = "Drupal\effective_activism\Form\ExportForm",
 *       "edit" = "Drupal\effective_activism\Form\ExportForm",
 *       "publish" = "Drupal\effective_activism\Form\ExportPublishForm",
 *     },
 *     "access" = "Drupal\effective_activism\AccessControlHandler\ExportAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\effective_activism\RouteProvider\ExportHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "exports",
 *   revision_table = "exports_revision",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "revision" = "revision_id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   bundle_entity_type = "export_type",
 *   links = {
 *     "canonical" = "/o/{organization}/exports/{export}",
 *     "add-form" = "/o/{organization}/exports/add/{export_type}",
 *     "edit-form" = "/o/{organization}/exports/{export}/edit",
 *     "publish-form" = "/o/{organization}/exports/{export}/publish",
 *     "group-canonical" = "/o/{organization}/g/{group}/exports/{export}",
 *     "group-add-form" = "/o/{organization}/g/{group}/exports/add/{export_type}",
 *     "group-edit-form" = "/o/{organization}/g/{group}/exports/{export}/edit",
 *     "group-publish-form" = "/o/{organization}/g/{group}/exports/{export}/publish",
 *   },
 * )
 */
class Export extends RevisionableContentEntityBase implements ExportInterface {

  use EntityChangedTrait;

  const WEIGHTS = [
    'organization',
    'parent',
    'filter',
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
  public function getType() {
    return $this->bundle();
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
    $fields['revision_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('The Revision ID of the Export entity.'))
      ->setReadOnly(TRUE);
    $fields['organization'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Organization'))
      ->setDescription(t('The organization of the export.'))
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
        'type' => 'entity_reference_autocomplete',
        'weight' => array_search('organization', self::WEIGHTS),
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ]);
    $fields['parent'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent'))
      ->setDescription(t('The group of the export.'))
      ->setRequired(FALSE)
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'group')
      ->setSetting('handler', 'default')
      ->setCardinality(1)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => array_search('group', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => array_search('group', self::WEIGHTS),
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ]);
    $fields['filter'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Filter'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'filter')
      ->setSetting('handler', 'default')
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => array_search('filter', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'filter_selector',
        'settings' => [
          'allow_new' => FALSE,
          'allow_existing' => TRUE,
        ],
        'weight' => array_search('filter', self::WEIGHTS),
      ]);
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Export entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
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
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Export is published.'))
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
