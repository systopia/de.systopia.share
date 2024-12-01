<?php
namespace Civi\Share;

use Civi\Core\Event\GenericHookEvent as Event;
use Civi\Api4\ShareChange;
use Civi\Api4\ShareNode;

/**
 * CiviShare ChangeProcessingEvent
 *
 * The purpose of this class is to process/apply a set of changes
 *  to the system.
 *
 * It will trigger a symfony hook to determine which processor instances would
 *  want to process the change at hand an will make them apply it.
 *
 * Processors can have a look a the set of changes to determine whether they
 *  can process it.
 *
 * Provided by the CiviShare extension.
 */
class ChangeProcessingEvent extends Event
{

  /** @var int node ID */
  protected int $node_id;

  /** @var int change ID */
  protected int $change_id;

  /** @var array node data */
  protected ?array $node_data = null;

  /** @var array change data */
  protected ?array $change_data = null;

  /** @var bool has this change been processed? */
  protected bool $is_processed = false;

  /** @var string the new status after the processing */
  protected string $new_status = 'DONE';

  /**
   * Create a new change processor for the given node. You can also add APIv4 data
   *   of the node if you have it.
   *
   * @param int $node_id
   *   ID of the node the changes should be processed on.
   *
   * @param array $change_data
   *   APIv4 data of the change, in case you have it - will otherwise be loaded
   *
   * @param array $node_data
   *   APIv4 data of the node, in case you have it - will otherwise be loaded
   */
  public function __construct($change_id, $node_id, $change_data = null, $node_data = null)
  {
    $this->node_id = $node_id;
    $this->change_id = $change_id;
    $this->is_processed = false;
    $this->change_data = $change_data;
    $this->node_data = $node_data;

    // load node data if not given
    if (empty($this->change_data)) {
      $change_data = \Civi\Api4\ShareChange::get(TRUE)
        ->addWhere('id', '=', $change_id)
        ->setLimit(1)
        ->execute()
        ->first();
    }

    // load node data if not given
    if (empty($this->node_data)) {
      $this->node_data = \Civi\Api4\ShareNode::get(TRUE)
        ->addWhere('id', '=', $node_id)
        ->setLimit(1)
        ->execute()
        ->first();
    }
  }

  /**
   * Has this change been procesed?
   *
   * @return bool
   */
  public function isProcessed()
  {
    return $this->is_processed;
  }

  /**
   * Has this change been procesed?
   *
   * @return bool
   */
  public function setProcessed($is_processed = true)
  {
    $this->is_processed = $is_processed;
  }

  /**
   * Get the new status for the CHANGE object being processed
   *
   * @return string
   */
  public function getNewStatus()
  {
    return $this->new_status;
  }

  /**
   * Set the new status for the CHANGE object being processed
   *
   * @param string $status
   * @return void
   */
  public function setNewStatus($status)
  {
    return $this->new_status;
  }
}
