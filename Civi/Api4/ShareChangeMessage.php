<?php

namespace Civi\Api4;

use Civi\Api4\Generic\AbstractEntity;
use Civi\Share\Api4\Action\ShareChangeMessage\GetFieldsAction;
use Civi\Share\Api4\Action\ShareChangeMessage\ReceiveAction;
use Civi\Share\Permissions;

class ShareChangeMessage extends Generic\AbstractEntity {

  /**
   * @inheritDoc
   */
  public static function getFields(bool $checkPermissions = TRUE) {
    return (new GetFieldsAction())->setCheckPermissions($checkPermissions);
  }

  public static function receive(bool $checkPermissions = TRUE) {
    return (new ReceiveAction())->setCheckPermissions($checkPermissions);
  }

  /**
   * @inheritDoc
   */
  public static function permissions() {
    return [
      'receive' => [Permissions::RECEIVE_CHANGE_MESSAGES],
    ];
  }

}
