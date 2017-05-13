<?php

namespace Drupal\effective_activism;

/**
 * Provides constants.
 */
class Constant {

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
  ];

  const GROUP_DEFAULT_VALUES = [
    'title' => 'My first group',
  ];

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
   * Cache tags.
   */
  const CACHE_TAG_USER = 'user';
  const CACHE_TAG_ORGANIZATION = 'organization_list';
  const CACHE_TAG_GROUP = 'group_list';
  const CACHE_TAG_IMPORT = 'import_list';
  const CACHE_TAG_EVENT = 'event_list';
  const CACHE_TAG_RESULT_TYPE = 'config:result_type_list';

}
