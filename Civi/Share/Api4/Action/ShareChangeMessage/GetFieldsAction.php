<?php

declare(strict_types = 1);

namespace Civi\Share\Api4\Action\ShareChangeMessage;

use CRM_Share_ExtensionUtil as E;
use Civi\Api4\Generic\BasicGetFieldsAction;
use Civi\Api4\ShareChangeMessage;

class GetFieldsAction extends BasicGetFieldsAction {

  public function __construct() {
    return parent::__construct(ShareChangeMessage::getEntityName(), 'getFields');
  }

  /**
   * @phpstan-return list<array<string, array<string, scalar>|array<scalar>|scalar|null>>
   */
  protected function getRecords(): array {
    return [
      [
        'name' => 'message',
        'title' => E::ts('Change Message'),
        'type' => 'Field',
        'nullable' => FALSE,
        'data_type' => 'String',
        'serialize' => \CRM_Core_DAO::SERIALIZE_JSON,
        'readonly' => TRUE,
      ],
    ];
  }

}
