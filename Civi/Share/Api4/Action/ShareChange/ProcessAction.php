<?php

namespace Civi\Share\Api4\Action\ShareChange;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Api4\ShareChange;
use Civi\Share\Change;
use Civi\Share\ChangeProcessingEvent;

/**
 * @method int getLocalNodeId()
 * @method $this setLocalNodeId(int $localNodeId)
 * @method int getId()
 * @method $this setId(int $id)
 */
class ProcessAction extends AbstractAction {

  /**
   * @var int
   *   The ID of the CiviShare Node on which to process the change.
   * @required
   */
  protected ?int $localNodeId = NULL;

  /**
   * @var int
   *   The ID of the CiviShare Change to process.
   */
  protected ?int $id = NULL;

  public function __construct() {
    parent::__construct(ShareChange::getEntityName(), 'process');
  }

  /**
   * @inheritDoc
   */
  public function _run(Result $result): void {
    $query = ShareChange::get()
      ->addWhere('status', '=', Change::STATUS_PENDING);
    if (isset($this->id)) {
      $query
        ->addWhere('id', '=', $this->id);
    }
    $shareChanges = $query->execute();

    foreach ($shareChanges as $shareChange) {
      $change = Change::createFromApiResultArray($shareChange);
      $change->process($this->localNodeId);
    }
  }

}
