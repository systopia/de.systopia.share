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
 * @method void setSourceNodeId(int $sourceNodeId)
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
    $peerings = ShareNodePeering::get(FALSE)
      ->addSelect('remote_node')
      ->addWhere('local_node', '=', $this->sourceNodeId)
      ->addWhere('is_enabled', '=', TRUE)
      ->execute();
    if (0 === $peerings->count()) {
      return;
    }

    $shareChanges = ShareChange::get()
      ->addSelect('id', 'change_type', 'local_contact_id', 'source_node_id', 'change_date', 'received_date', 'data_before', 'data_after')
      ->addWhere('source_node_id', '=', $this->sourceNodeId)
      ->addWhere('status', 'IN', Change::PENDING_FROM_SENDING_STATUS)
      ->execute();
    if (0 === $shareChanges->count()) {
      return;
    }

    $message = new Message();
    $message->setSenderNodeId($this->sourceNodeId);
    foreach ($shareChanges as $shareChange) {
      $change = new Change(
        $shareChange['change_type'],
        $shareChange['local_contact_id'],
        $shareChange['source_node_id'],
        Change::parseAttributeChanges($shareChange['data_before'] ?? [], $shareChange['data_after'] ?? []),
        \DateTime::createFromFormat(Utils::CIVICRM_DATE_FORMAT, $shareChange['change_date']),
        isset($shareChange['received_date']) ? \DateTime::createFromFormat(Utils::CIVICRM_DATE_FORMAT, $shareChange['received_date']) : NULL,
        $shareChange['id']
      );
      $message->addChange($change);
    }
    $serializedMessade = $message->serialize();

    foreach ($peerings as $peering) {
      \Civi::service('civi.share.api')->sendMessage($peering['id'], $serializedMessade);
    }

    // TODO: Store to result.
  }

}
