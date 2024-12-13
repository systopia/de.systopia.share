<?php

namespace Civi\Share;

use CRM_Share_ExtensionUtil as E;

class Permissions {

  public const RECEIVE_CHANGE_MESSAGES = 'receive change messages';

  public static function getPermissions(): array {
    return [
      self::RECEIVE_CHANGE_MESSAGES => [
        'label' => E::ts('Receive Change Messages'),
        'description' => E::ts('Allows receiving change messages via'),
      ],
    ];
  }

}
