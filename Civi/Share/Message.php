<?php
namespace Civi\Share;

use Civi\Api4\ShareChange;
use Civi\Api4\ShareNode;

/**
 * CiviShare Message object (transient)
 *
 * A message object contains a series of ShareChange ids
 *  along with metadata
 *
 * This class provides functions to
 *  - serialise/encrypt
 *  - deserialise/decrypt
 *  - send
 *  - verify
 *  - loop detection
 *
 * Provided by the CiviShare extension.
 *
 * @package Civi\Api4
 */
class Message
{

  /** @var array list of change ids */
  protected array $change_ids = [];

  /**
   * Create a new, empty message
   */
  public function __construct()
  {

  }

  /**
   * Add a change ID to the message
   *
   * @param int $change_id
   *
   * @return void
   */
  public function addChangeById(int $change_id)
  {
    $this->change_ids[] = $change_id;
  }

  /**
   * Mark all changes in this message with the given status
   *
   * @param string $status
   *  one of LOCAL, PENDING, BUSY, FORWARD, DONE, DROPPED, ERROR
   * @return void
   */
  public function markChanges($status)
  {
    foreach ($this->change_ids as $change_id) {
      // TODO: can we do this in one call?
      $results = \Civi\Api4\ShareChange::update(TRUE)
        ->addValue('status', $status)
        ->addWhere('id', '=', $change_id)
        ->execute();
    }
  }

  /**
   * Send out a compiled message
   *
   * @param int $local_node_id
   *    ID of the local node. Only required if multiple local nodes present
   *
   * @return void
   *
   * @throws \CRM_Core_Exception
   */
  public function send($local_node_id = null)
  {
    // select the changes for the payload
    $lock = \Civi::lockManager()->acquire('data.civishare.changes'); // is 'data' the right type?

    // get the local node
    // TODO: cache?
    if (empty($local_node_id)) {
      // first: get the source node, and then find nodes to send these changes to
      $local_node = \Civi\Api4\ShareNode::get(TRUE)
        ->addSelect('is_local')
        ->addWhere('is_local', '=', TRUE)
        ->setLimit(1)
        ->execute()
        ->first(); // TODO: What if there's multiple ones?
      $local_node_id = $local_node['id'];
    }

    // get target nodes
    $peerings = \Civi\Api4\ShareNodePeering::get(TRUE)
      ->addSelect('shared_secret', 'remote_node.*', '*')
      ->addWhere('local_node', '=', $local_node_id)
      ->addWhere('is_enabled', '=', 1)
      ->execute();

    if ($peerings->count()) {
      foreach ($peerings as $peered_node) {
        // send message to every peered node
        if ($peered_node) {
          // shortcut: local-to-local connection
          if (empty($peered_node['remote_node.rest_url']) || empty($peered_node['remote_node.api_key'])) {
            // this is not a proper node...but we might allow this anyway:
            if (defined('CIVISHARE_ALLOW_LOCAL_LOOP')) {
              $this->processOnNode($peered_node['remote_node.id']);
            }
          } else {
            // TODO: implement SENDING
            \Civi::log()->warning("todo: implement serialisation and sending (directly/queue/etc)");
          }

          // todo: mark as SENT

        }
      }

    } else {
      // no peered instances: mark changes as DROPPED
      $this->markChanges('DROPPED');
    }

    $lock->release();
  }

  /**
   * Process the received and verified message on the given node
   *
   * @param int $local_node_id
   *   the node where this will be processed on
   *
   * @return void
   */
  public function processOnNode($local_node_id)
  {
    $lock = \Civi::lockManager()->acquire('data.civishare.changes'); // is 'data' the right type?

    // load the changes
    $changes = civicrm_api4('ShareChange', 'get', [
      'where' => [
        ['id', 'IN', $this->change_ids],
      ],
      'checkPermissions' => TRUE,
    ]);

    // process them
    $change_processor = new ChangeProcessor($local_node_id);
    $change_processor->addChanges($changes);
    $change_processor->process();

    $lock->release();
  }


  /**
   * Apply the given change by extracting the relevant change handler
   *
   * @param array $change
   *    change data
   *
   * @return void
   */
  public function applyChange(array $change)
  {
    // @todo rename handler_class to change_type
    $change_type = $change['handler_class'];



  }


  /**
   * Parse/reconstruct a message
   *
   * @param string $data
   *   a raw data packed (encrypted and serialised)
   *
   * @param int node_peering ID
   *   the ID of an active peering object
   *
   * @return ?Message
   */
  public static function extractSerialisedMessage($data, $node_peering_id) : ?Message
  {
    // todo
  }
}
