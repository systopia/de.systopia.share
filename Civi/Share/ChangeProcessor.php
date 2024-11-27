<?php
namespace Civi\Share;

use Civi\Api4\ShareChange;
use Civi\Api4\ShareNode;

/**
 * CiviShare ChangeProcessor
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
public class ChangeProcessor
{

  /** @var int node ID */
  protected int $node_id;

  /** @var array node data */
  protected int $node_data = null;

  /**
   * Create a new change processor for the given node. You can also add APIv4 data
   *   of the node if you have it.
   *
   * @param int $node_id
   *   ID of the node the changes should be processed on.
   *
   * @param array $node_data
   *   APIv4 data of the node, in case you have it - will otherwise be loaded
   */
  public function __construct($node_id, $node_data = null)
  {
    $this->node_id = $node_id;
    $this->node_data = $node_data;
    if (is_null($this->node_data)) {
      $node_data = \Civi\Api4\ShareNode::get(TRUE)
        ->addSelect('is_local')
        ->addWhere('id', '=', $node_id)
        ->setLimit(1)
        ->execute()
        ->first();
    }
  }

  /**
   * Process the given change IDs
   */
  public static processChanges($change_ids, $changes) {
    Civi:
  }
}
