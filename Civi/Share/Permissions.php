<?php

declare(strict_types = 1);

namespace Civi\Share;

use CRM_Share_ExtensionUtil as E;

class Permissions {

  public const NODE_READ = 'civishare read nodes';

  public const NODE_PEERING_READ = 'civishare read node peerings';

  public const CHANGE_CREATE = 'civishare create changes';

  public const CHANGE_READ = 'civishare read changes';

  public const CHANGE_MESSAGE_RECEIVE = 'civishare receive change messages';

  public static function getPermissions(): array {
    return [
      self::NODE_READ => [
        'label' => E::ts('CiviShare: View Nodes'),
        'description' => E::ts('Allows retrieving/viewing CiviShare nodes.'),
      ],
      self::NODE_PEERING_READ => [
        'label' => E::ts('CiviShare: View Node Peerings'),
        'description' => E::ts('Allows retrieving/viewing CiviShare node peerings.'),
      ],
      self::CHANGE_READ => [
        'label' => E::ts('CiviShare: View Changes'),
        'description' => E::ts('Allows retrieving/viewing change records.'),
      ],
      self::CHANGE_CREATE => [
        'label' => E::ts('CiviShare: Create Changes'),
        'description' => E::ts('Allows creating change records.'),
      ],
      self::CHANGE_MESSAGE_RECEIVE => [
        'label' => E::ts('CiviShare: Receive Change Messages'),
        'description' => E::ts('Allows receiving change messages.'),
      ],
    ];
  }

}
