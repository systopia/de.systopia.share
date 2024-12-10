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

  /** ChangeProcessor priority PRIORITY: use if you definitely want the have a first go a this */
  public const PRIORITY_PROCESSING = 1000;

  /** ChangeProcessor priority is very high: use to override the default processing */
  const EARLY_PROCESSING = 750;

  /** ChangeProcessor priority is high: use to override the default processing */
  const PREFERRED_PROCESSING = 500;

  /** ChangeProcessor priority is default */
  const DEFAULT_PROCESSING = 250;

  /** ChangeProcessor priority is low */
  const LATE_PROCESSING = 100;

  /** ChangeProcessor priority: after the regular processing */
  const POST_PROCESSING = 50;

  /** ChangeProcessor priority REPORTING: at this point everything should've happened */
  const REPORTING = 0;


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
  protected string $new_change_status = 'DONE';

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
      $this->change_data = \Civi\Api4\ShareChange::get(TRUE)
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
  public function getNewChangeStatus()
  {
    return $this->$new_change_status;
  }

  /**
   * Set the new status for the CHANGE object being processed
   *
   * @param string $status
   * @return void
   */
  public function setNewChangeStatus($status)
  {
    $this->$new_change_status = $status;
  }

  /**
   * Check if the change being processed is the given type
   *
   * @param $change_type
   * @return void
   */
  public function hasChangeType($change_type)
  {
    return $this->change_data['change_type'] == $change_type;
  }

  /**
   * Get the new status of the change as suggested by the change processor(s)
   *
   * @return string
   */
  public function getNewStatus() : string
  {
    return $this->new_change_status;
  }

  /**
   * Helper to deserialise JSON data in the Change object
   *
   * @param string $serialised_data
   * @return array
   */
  protected function getJsonData($serialised_data)
  {
    $data = json_decode($serialised_data, true);
    // todo: error handling
    return $data;
  }

  /**
   * Get the data BEFORE the change
   *
   * @return array
   */
  public function getChangeDataBefore()
  {
    // todo: cache? might be tricky...
    return $this->getJsonData($this->change_data['data_before']);
  }

  /**
   * Get the data AFTER the change
   *
   * @return array
   */
  public function getChangeDataAfter()
  {
    // todo: cache? might be tricky...
    return $this->getJsonData($this->change_data['data_after']);
  }

  /**
   * Get the data AFTER the change
   *
   * @return ?int
   */
  public function getContactID()
  {
    $before_data_contact_id = $this->getChangeDataBefore()['contact_id'] ?? null;
    $after_data_contact_id = $this->getChangeDataAfter()['contact_id'] ?? null;
    if ($before_data_contact_id && $after_data_contact_id && $before_data_contact_id != $after_data_contact_id) {
      // todo: log as contact_id conflict
      return null; // there is a conflict
    } else {
      return $after_data_contact_id ?? $before_data_contact_id ?? null;
    }
  }

  /**
   * Get the attributes of the change entiti being processed right now
   *
   * @return array
   */
  public function getChange() : array
  {
    return $this->change_data;
  }
}
