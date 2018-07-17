<?php

namespace Drupal\effective_activism;

/**
 * Provides constants.
 */
class Constant {

  /**
   * General constants.
   */
  const MODULE_NAME = 'effective_activism';

  /**
   * Entities.
   */
  const ENTITY_ORGANIZATION = 'organization';
  const ENTITY_GROUP = 'group';
  const ENTITY_EVENT = 'event';
  const ENTITY_EVENT_TEMPLATE = 'event_template';
  const ENTITY_EXPORT = 'export';
  const ENTITY_EXPORT_TYPE = 'export_type';
  const ENTITY_FILTER = 'filter';
  const ENTITY_RESULT = 'result';
  const ENTITY_RESULT_TYPE = 'result_type';
  const ENTITY_DATA = 'data';
  const ENTITY_DATA_TYPE = 'data_type';
  const ENTITY_IMPORT = 'import';
  const ENTITY_IMPORT_TYPE = 'import_type';
  const ENTITY_THIRD_PARTY_CONTENT = 'third_party_content';
  const ENTITY_THIRD_PARTY_CONTENT_TYPE = 'third_party_content_type';

  /**
   * Slugs for parameter upcasting.
   */
  const SLUG_POSTFIX = '_slug';
  const SLUG_ORGANIZATION = self::ENTITY_ORGANIZATION . self::SLUG_POSTFIX;
  const SLUG_GROUP = self::ENTITY_GROUP . self::SLUG_POSTFIX;
  const SLUG_EVENT = self::ENTITY_EVENT . self::SLUG_POSTFIX;
  const SLUG_EVENT_TEMPLATE = self::ENTITY_EVENT_TEMPLATE . self::SLUG_POSTFIX;
  const SLUG_EXPORT = self::ENTITY_EXPORT . self::SLUG_POSTFIX;
  const SLUG_EXPORT_TYPE = self::ENTITY_EXPORT_TYPE . self::SLUG_POSTFIX;
  const SLUG_FILTER = self::ENTITY_FILTER . self::SLUG_POSTFIX;
  const SLUG_RESULT = self::ENTITY_RESULT . self::SLUG_POSTFIX;
  const SLUG_RESULT_TYPE = self::ENTITY_RESULT_TYPE . self::SLUG_POSTFIX;
  const SLUG_DATA = self::ENTITY_DATA . self::SLUG_POSTFIX;
  const SLUG_DATA_TYPE = self::ENTITY_DATA_TYPE . self::SLUG_POSTFIX;
  const SLUG_IMPORT = self::ENTITY_IMPORT . self::SLUG_POSTFIX;
  const SLUG_IMPORT_TYPE = self::ENTITY_IMPORT_TYPE . self::SLUG_POSTFIX;
  const SLUG_THIRD_PARTY_CONTENT = self::ENTITY_THIRD_PARTY_CONTENT . self::SLUG_POSTFIX;
  const SLUG_THIRD_PARTY_CONTENT_TYPE = self::ENTITY_THIRD_PARTY_CONTENT_TYPE . self::SLUG_POSTFIX;

  /**
   * The default result types.
   */
  const DEFAULT_RESULT_TYPES = [
    'leafleting' => [
      'label' => 'Leafleting',
      'description' => 'Distribute flyers on sidewalks, city squares, public events and colleges.',
      'datatypes' => [
        'leaflets' => 'leaflets',
      ],
    ],
    'signature_collection' => [
      'label' => 'Signature collection',
      'description' => 'Ask people for a signature ( and usually an e-mail address ) to support a cause.',
      'datatypes' => [
        'signatures' => 'signatures',
      ],
    ],
    'pay_per_view_event' => [
      'label' => '"pay-per-view" event',
      'description' => 'Pay people a small amount of money for watching a movie.',
      'datatypes' => [
        'paid_views' => 'paid_views',
      ],
    ],
    'fundraising' => [
      'label' => 'Fundraising',
      'description' => 'Gather monetary contributions by donations such as door to door collections or on the street.',
      'datatypes' => [
        'income' => 'income',
      ],
    ],
  ];

  const GROUP_DEFAULT_VALUES = [
    'title' => 'My first group',
  ];

  const GROUP_INHERIT_TIMEZONE = 'inherit';

  /**
   * Cache table for storing addresses.
   */
  const LOCATION_CACHE_TABLE = 'effective_activism_location_addresses';

  /**
   * Invitation table.
   */
  const INVITATION_TABLE = 'effective_activism_invitations';

  /**
   * Manager role.
   */
  const ROLE_MANAGER = 1;

  /**
   * Organizer role.
   */
  const ROLE_ORGANIZER = 2;

  /**
   * Third-party content types.
   */
  const THIRD_PARTY_CONTENT_TYPE_WEATHER_INFORMATION = 'weather_information';
  const THIRD_PARTY_CONTENT_TYPE_DEMOGRAPHICS = 'demographics';
  const THIRD_PARTY_CONTENT_TYPE_EXTENDED_LOCATION_INFORMATION = 'extended_location_information';

  /**
   * Cache tags.
   */
  const CACHE_TAG_USER = 'user';
  const CACHE_TAG_ORGANIZATION = 'organization_list';
  const CACHE_TAG_GROUP = 'group_list';
  const CACHE_TAG_IMPORT = 'import_list';
  const CACHE_TAG_EXPORT = 'export_list';
  const CACHE_TAG_FILTER = 'filter_list';
  const CACHE_TAG_EVENT = 'event_list';
  const CACHE_TAG_EVENT_TEMPLATE = 'event_template_list';
  const CACHE_TAG_RESULT_TYPE = 'config:result_type_list';

  /**
   * Mail keys.
   */
  const MAIL_KEY_INVITATION_MANAGER = self::MODULE_NAME . '_invitation_manager';
  const MAIL_KEY_INVITATION_ORGANIZER = self::MODULE_NAME . '_invitation_organizer';

  /**
   * Event creation.
   */
  const EVENT_CREATION_ALL = '0';
  const EVENT_CREATION_EVENT = '1';
  const EVENT_CREATION_EVENT_TEMPLATE = '2';

  /**
   * Result type constants.
   */
  const RESULT_TYPE_ALL_GROUPS = '-1';

}
