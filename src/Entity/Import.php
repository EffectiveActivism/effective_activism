<?php

namespace Drupal\effective_activism\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the Import entity.
 *
 * @ingroup effective_activism
 *
 * @ContentEntityType(
 *   id = "import",
 *   label = @Translation("Import"),
 *   bundle_label = @Translation("Import type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\effective_activism\ListBuilder\ImportListBuilder",
 *     "form" = {
 *       "default" = "Drupal\effective_activism\Form\ImportForm",
 *       "add" = "Drupal\effective_activism\Form\ImportForm",
 *       "edit" = "Drupal\effective_activism\Form\ImportForm",
 *       "publish" = "Drupal\effective_activism\Form\ImportPublishForm",
 *     },
 *     "access" = "Drupal\effective_activism\AccessControlHandler\ImportAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\effective_activism\RouteProvider\ImportHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "imports",
 *   revision_table = "imports_revision",
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
 *   bundle_entity_type = "import_type",
 *   links = {
 *     "canonical" = "/manage/imports/{import}",
 *     "add-form" = "/manage/imports/add/{import_type}",
 *     "edit-form" = "/manage/imports/{import}/edit",
 *     "publish-form" = "/manage/imports/{import}/publish",
 *     "collection" = "/manage/imports",
 *   },
 * )
 */
class Import extends RevisionableContentEntityBase implements ImportInterface {

  use EntityChangedTrait;

  const WEIGHTS = [
    'parent',
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
      ->setDescription(t('The Revision ID of the Import entity.'))
      ->setReadOnly(TRUE);
    $fields['parent'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Group'))
      ->setDescription(t('The group that this import belongs to.'))
      ->setRevisionable(TRUE)
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
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Import entity.'))
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
      ->setDescription(t('A boolean indicating whether the Import is published.'))
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
