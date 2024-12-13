<?php
namespace Civi\Api4;

use Civi\Share\Permissions;

/**
 * ShareNodePeering entity.
 *
 * Provided by the CiviShare extension.
 *
 * @package Civi\Api4
 */
class ShareNodePeering extends Generic\DAOEntity {

  /**
   * @inheritDoc
   */
  public static function permissions() {
    return [
      'meta' => ['access CiviCRM'],
      'default' => ['administer CiviCRM'],
      'get' => [Permissions::NODE_PEERING_READ],
    ];
  }

}
