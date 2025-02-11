<?php

declare(strict_types = 1);

namespace Civi\Api4;

use Civi\Share\Api4\Action\ShareChangeMessage\GetFieldsAction;
use Civi\Share\Api4\Action\ShareChangeMessage\SendAction;
use Civi\Share\Api4\Action\ShareChangeMessage\ReceiveAction;
use Civi\Share\Permissions;

class ShareChangeMessage extends Generic\AbstractEntity {

  /**
   * @inheritDoc
   */
  public static function getFields(bool $checkPermissions = TRUE): GetFieldsAction {
    return (new GetFieldsAction())->setCheckPermissions($checkPermissions);
  }

  public static function send(bool $checkPermissions = TRUE): SendAction {
    return (new SendAction())->setCheckPermissions($checkPermissions);
  }

  public static function receive(bool $checkPermissions = TRUE): ReceiveAction {
    return (new ReceiveAction())->setCheckPermissions($checkPermissions);
  }

  /**
   * @inheritDoc
   */
  public static function permissions() {
    return [
      'meta' => ['access CiviCRM'],
      'default' => ['administer CiviCRM'],
      'receive' => [Permissions::CHANGE_MESSAGE_RECEIVE],
    ];
  }

}
