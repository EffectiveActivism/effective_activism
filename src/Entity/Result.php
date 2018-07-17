<?php

namespace Drupal\effective_activism\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the Result entity.
 *
 * @ingroup effective_activism
 *
 * @ContentEntityType(
 *   id = "result",
 *   label = @Translation("Result"),
 *   bundle_label = @Translation("Result type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "access" = "Drupal\effective_activism\AccessControlHandler\ResultAccessControlHandler",
 *   },
 *   base_table = "results",
 *   revision_table = "results_revision",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "revision" = "revision_id",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   bundle_entity_type = "result_type",
 * )
 */
class Result extends RevisionableContentEntityBase implements ResultInterface {

  use EntityChangedTrait;

  const WEIGHTS = [
    'user_id',
    'participant_count',
    'duration_minutes',
    'duration_hours',
    'duration_days',
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
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Result entity.'))
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
    $fields['participant_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Number of participants'))
      ->setDescription(t('Type in the number of participants.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'min' => 0,
        'max' => 999,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => array_search('participant_count', self::WEIGHTS),
        'settings' => [
          'placeholder' => t('Participants'),
        ],
      ])
      ->setDisplayOptions('form', [
        'weight' => array_search('participant_count', self::WEIGHTS),
      ]);
    $fields['duration_minutes'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Minutes'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setSettings([
        'allowed_values' => [
          '0' => '0',
          '1' => '1',
          '2' => '2',
          '3' => '3',
          '4' => '4',
          '5' => '5',
          '6' => '6',
          '7' => '7',
          '8' => '8',
          '9' => '9',
          '10' => '10',
          '11' => '11',
          '12' => '12',
          '13' => '13',
          '14' => '14',
          '15' => '15',
          '16' => '16',
          '17' => '17',
          '18' => '18',
          '19' => '19',
          '20' => '20',
          '21' => '21',
          '22' => '22',
          '23' => '23',
          '24' => '24',
          '25' => '25',
          '26' => '26',
          '27' => '27',
          '28' => '28',
          '29' => '29',
          '30' => '30',
          '31' => '31',
          '32' => '32',
          '33' => '33',
          '34' => '34',
          '35' => '35',
          '36' => '36',
          '37' => '37',
          '38' => '38',
          '39' => '39',
          '40' => '40',
          '41' => '41',
          '42' => '42',
          '43' => '43',
          '44' => '44',
          '45' => '45',
          '46' => '46',
          '47' => '47',
          '48' => '48',
          '49' => '49',
          '50' => '50',
          '51' => '51',
          '52' => '52',
          '53' => '53',
          '54' => '54',
          '55' => '55',
          '56' => '56',
          '57' => '57',
          '58' => '58',
          '59' => '59',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => array_search('duration_minutes', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => array_search('duration_minutes', self::WEIGHTS),
      ]);
    $fields['duration_hours'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Hours'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setSettings([
        'allowed_values' => [
          '0' => '0',
          '1' => '1',
          '2' => '2',
          '3' => '3',
          '4' => '4',
          '5' => '5',
          '6' => '6',
          '7' => '7',
          '8' => '8',
          '9' => '9',
          '10' => '10',
          '11' => '11',
          '12' => '12',
          '13' => '13',
          '14' => '14',
          '15' => '15',
          '16' => '16',
          '17' => '17',
          '18' => '18',
          '19' => '19',
          '20' => '20',
          '21' => '21',
          '22' => '22',
          '23' => '23',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => array_search('duration_hours', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => array_search('duration_hours', self::WEIGHTS),
      ]);
    $fields['duration_days'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Days'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setSettings([
        'allowed_values' => [
          '0' => '0',
          '1' => '1',
          '2' => '2',
          '3' => '3',
          '4' => '4',
          '5' => '5',
          '6' => '6',
          '7' => '7',
          '8' => '8',
          '9' => '9',
          '10' => '10',
          '11' => '11',
          '12' => '12',
          '13' => '13',
          '14' => '14',
          '15' => '15',
          '16' => '16',
          '17' => '17',
          '18' => '18',
          '19' => '19',
          '20' => '20',
          '21' => '21',
          '22' => '22',
          '23' => '23',
          '24' => '24',
          '25' => '25',
          '26' => '26',
          '27' => '27',
          '28' => '28',
          '29' => '29',
          '30' => '30',
          '31' => '31',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => array_search('duration_days', self::WEIGHTS),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => array_search('duration_days', self::WEIGHTS),
      ]);
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(TRUE)
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Result is published.'))
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
