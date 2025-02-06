<?php

declare(strict_types = 1);

namespace Civi\Share\Api4\Action\ShareChangeMessage;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Api4\ShareChangeMessage;
use Civi\Share\Message;

/**
 * @method array getMessage()
 * @method $this setMessage(array $message)
 */
class ReceiveAction extends AbstractAction {

  /**
   * @var array
   * A JSON-formatted change message.
   * @required
   */
  protected ?array $message = NULL;

  public function __construct() {
    parent::__construct(ShareChangeMessage::getEntityName(), 'receive');
  }

  /**
   * @inheritDoc
   */
  public function _run(Result $result) {
    $message = Message::createFromSerializedMessage($this->getMessage());
    $message
      ->persistChanges();
    $result->rowCount = count($message->getPersistedChangeIds());
  }

}
