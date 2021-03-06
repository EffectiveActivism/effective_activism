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
  const THIRD_PARTY_CONTENT_TYPE_CITY_PULSE = 'city_pulse';
  const THIRD_PARTY_CONTENT_TYPE_DEMOGRAPHICS = 'demographics';
  const THIRD_PARTY_CONTENT_TYPE_EXTENDED_LOCATION_INFORMATION = 'extended_location_information';
  const THIRD_PARTY_CONTENT_TYPE_WEATHER_INFORMATION = 'weather_information';

  /**
   * The third-party content that uses time.
   */
  const THIRD_PARTY_CONTENT_TIME_AWARE = [
    self::THIRD_PARTY_CONTENT_TYPE_CITY_PULSE,
    self::THIRD_PARTY_CONTENT_TYPE_WEATHER_INFORMATION,
  ];

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
