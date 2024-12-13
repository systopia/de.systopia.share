<?php
namespace Civi\Api4;

use Civi\Share\Permissions;

/**
 * ShareChange entity.
 *
 * Provided by the CiviShare extension.
 *
 * @package Civi\Api4
 */
class ShareChange extends Generic\DAOEntity {

  /**
   * @inheritDoc
   */
  public static function permissions() {
    return [
      'meta' => ['access CiviCRM'],
      'default' => ['administer CiviCRM'],
      'get' => [Permissions::CHANGE_READ],
      'create' => [Permissions::CHANGE_CREATE],
    ];
  }

}
