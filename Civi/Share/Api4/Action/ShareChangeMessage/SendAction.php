<?php

namespace Civi\Share\Api4\Action\ShareChangeMessage;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Api4\ShareChange;
use Civi\Api4\ShareChangeMessage;
use Civi\Api4\ShareNodePeering;
use Civi\Share\Change;
use Civi\Share\Message;
use Civi\Share\Utils;

/**
 * @method int getSourceNodeId()
 * @method $this setSourceNodeId(int $sourceNodeId)
 */
class SendAction extends AbstractAction {

  /**
   * @var int
   *   The ID of the CiviShare Node of which to distribute changes.
   * @required
   */
  protected ?int $sourceNodeId = NULL;

  public function __construct() {
    parent::__construct(ShareChangeMessage::getEntityName(), 'send');
  }

  /**
   * @inheritDoc
   */
  public function _run(Result $result): void {
    $sendResults = [];
    foreach (Message::generateForSourceNode($this->sourceNodeId) as $message) {
      $message->setSenderNodeId($this->sourceNodeId);
      $sendResults[] = $message->send();
    }
    $result->exchangeArray($sendResults);
  }

}
