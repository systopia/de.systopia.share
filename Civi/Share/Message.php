<?php
namespace Civi\Share;

use Civi\Api4\ShareChange;
use Civi\Api4\ShareNode;
use Civi\Share\ChangeProcessingEvent;
use Civi\Funding\Event\FundingCase\GetPossibleFundingCaseStatusEvent;
use Civi\Share\CiviMRF\CiviMRFClient;
use Civi\Share\CiviMRF\ShareApi;

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

  /**
   * @phpstan-var list<\Civi\Share\Change>
   */
  protected array $changes = [];

  protected ?string $senderNodeId = NULL;

  public function setSenderNodeId(?string $senderNodeId): void {
    $this->senderNodeId = $senderNodeId;
  }

  /**
   * @var \Civi\Share\CiviMRF\CiviMRFClient
   * @inject civi.share.civimrf_client
   */
  protected $civiMRFClient;

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

  public function getChangeIds(): array {
    return array_map(function (Change $change) {
      return $change->getId();
    }, $this->changes);
  }

  public function getPersistedChangeIds(): array {
    return array_filter($this->getChangeIds());
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
      ->addWhere('id', 'IN',  $this->getPersistedChangeIds())
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
  public function send($local_node_id = NULL) {
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
      foreach ($peerings as $peering) {
        // send message to every peered node
        if ($peering) {
          // shortcut: local-to-local connection
          if (empty($peering['remote_node.rest_url']) || empty($peering['remote_node.api_key'])) {
            // this is not a proper node...but we might allow this anyway:
            if (defined('CIVISHARE_ALLOW_LOCAL_LOOP')) {
              $this->processOnNode($peering['remote_node.id']);
            }
          } else {
            // TODO: implement SENDING
            \Civi::log()->warning("todo: implement serialisation and sending (directly/queue/etc)");

            $shareApi = Civi::service('civi.share.api');
            $shareApi->sendMessage($peering['id'], $this->serialize());
          }

          // todo: mark as SENT

        }
      }
    }
    else {
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
  public function processChanges($local_node_id)
  {
    $lock = \Civi::lockManager()->acquire('data.civishare.changes'); // is 'data' the right type?

    // load the changes
    $changes = civicrm_api4('ShareChange', 'get', [
      'where' => [
        ['id', 'IN', $this->getPersistedChangeIds()],
      ],
      'checkPermissions' => TRUE,
    ]);

    // hand them over to the ChangeProcessor system
    foreach ($changes as $change) {
      $change_processor = new \Civi\Share\ChangeProcessingEvent($change['id'], $local_node_id, $change);
      try {
        \Civi::dispatcher()->dispatch(ChangeProcessingEvent::NAME, $change_processor);
        if ($change_processor->isProcessed()) {
          $this->setChangeStatus($change['id'], $change_processor->getNewStatus() ?? Change::STATUS_PROCESSED);
        } else {
          $this->setChangeStatus($change['id'], $change_processor->getNewStatus() ?? Change::STATUS_ERROR);
          \Civi::log()->warning("Change [{$change['id']}] could not be processed - no processor found.");
        }
      } catch (\Exception $exception) {
        \Civi::log()->warning("Change [{$change['id']}] failed processing, exception was " . $exception->getMessage());
        $this->setChangeStatus($change['id'], Change::STATUS_ERROR);
        $change_processor->setFailed($exception->getMessage());
      }
    }

    $lock->release();
  }

  /**
   * Update the change status
   *
   * @param int $change_id
   *    id of the change object
   *
   * @param string $status
   *    one of the pre-defined status strings
   *
   * @return void
   */
  public function setChangeStatus($change_id, $status)
  {
    // update change status
    // @todo check if necessary?
    // @todo check if one of the expected ones?
    \Civi\Api4\ShareChange::update(TRUE)
      ->addValue('status', $status)
      ->addWhere('id', '=', $change_id)
      ->execute();
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
        ['id', 'IN',  $this->getPersistedChangeIds()],
      ],
      'checkPermissions' => TRUE,
    ]);

    // process them
    foreach ($changes as $change) {
      $this->applyChange($change);
    }

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

    // @todo implement
  }

  /**
   * Check if all changes in the message have been processed
   *
   * @return boolean
   */
  public function allChangesProcessed()
  {
    // load the changes
    $changes = civicrm_api4('ShareChange', 'get', [
      'where' => [
        ['id', 'IN',  $this->getPersistedChangeIds()],
        ['status', 'IN', Change::ACTIVE_STATUS],
      ],
      'checkPermissions' => TRUE,
    ]);
    return $changes->count() == 0;
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
    // todo: implement
    throw new \Exception("not implemented");
    return null;
  }

  /**
   * @return array
   *   Representation of the message in sending format.
   */
  public function serialize(): array {
    $senderNode = ShareNode::get(FALSE)
      ->addSelect('short_name')
      ->addWhere('id', '=', $this->senderNodeId)
      ->execute()
      ->single();
    return [
      'payload' => [
        'sender' => $senderNode['short_name'],
        'sent' => date(Utils::DATE_FORMAT),
        'changes' => $this->serializeChanges(),
      ]
    ];
  }

  protected function serializeChanges() : array {
    $serializedChanges = [];
    foreach ($this->changes as $change) {
      $serializedChanges[] = $change->serialize();
    }
    return $serializedChanges;
  }

}
