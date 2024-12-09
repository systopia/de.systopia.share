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
class Message {

  /**
   * @phpstan-var list<\Civi\Share\Change>
   */
  protected array $changes = [];

  /**
   * Create a new, empty message
   */
  public function __construct() {

  }

  public static function createFromSerializedMessage(array $serializedMessage, ?\DateTimeInterface $receivedDate = NULL) {
    $message = new self();

    try {
      $sourceNodeId = ShareNode::get()
        ->addSelect('id')
        ->addWhere('short_name', '=', $serializedMessage['payload']['sender'])
        ->execute()
        ->single()['id'];
    }
    catch (\CRM_Core_Exception $e) {
      throw new \RuntimeException(
        sprintf(
          'Could not identify source node %s for change message %s.',
          $serializedMessage['payload']['sender'],
          $serializedMessage['id']
        ),
        0,
        $e
      );
    }

    // TODO: Add metadata properties.

    // Add changes.
    if (!is_array($serializedMessage['payload']['changes'])) {
      throw new \RuntimeException(
        sprintf('Could not parse changes for change message %s.', $serializedMessage['id'])
      );
    }
    try {
      foreach ($serializedMessage['payload']['changes'] as $serializedChange) {
        $message->addChange(Change::createFromSerialized($serializedChange, $sourceNodeId, $receivedDate));
      }
    }
    catch (\RuntimeException $e) {
      throw new \RuntimeException(
        sprintf('Could not parse changes for change message %s.', $serializedMessage['id'])
      );
    }

    return $message;
  }

  public function getChangeIds(): iterable {
    foreach ($this->changes as $change) {
      yield $change->getId();
    }
  }

  public function getPersistedChangeIds(): iterable {
    foreach ($this->changes as $change) {
      if (!$change->isPersisted()) {
        continue;
      }
      yield $change->getId();
    }
  }

  /**
   * Add a change to the message by its ID.
   */
  public function addChangeById(int $change_id): void {
    $this->changes[] = Change::createFromExisting($change_id);
  }

  public function addChange(Change $change): void {
    $this->changes[] = $change;
  }

  public function persistChanges(): void {
    foreach ($this->changes as $change) {
      $change->persist();
    }
  }

  /**
   * Mark all changes in this message with the given status
   *
   * @param string $status
   *  one of LOCAL, PENDING, BUSY, FORWARD, DONE, DROPPED, ERROR
   * @return void
   */
  public function markChanges(string $status): void {
    ShareChange::update(TRUE)
      ->addValue('status', $status)
      ->addWhere('id', 'IN', $this->getPersistedChangeIds())
      ->execute();
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
        ['id', 'IN', $this->getPersistedChangeIds()],
      ],
      'checkPermissions' => TRUE,
    ]);

    // process them
    foreach ($changes as $change) {
      $this->applyChange($change);
    }

    $lock->release();
  }

}
