<?php

declare(strict_types = 1);

namespace Civi\Share;

use Civi;
use Civi\Api4\ShareChange;
use Civi\Api4\ShareNode;
use Civi\Api4\ShareNodePeering;
use Civi\Share\CiviMRF\ShareApi;
use Civi\Share\Event\MessageGenerateEvent;

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

  protected ?int $senderNodeId = NULL;

  /**
   * @phpstan-var list<int>
   */
  protected array $targetNodePeeringIds = [];

  /**
   * @phpstan-var array<string, array<string, mixed>>
   */
  protected array $entityIdentificationContext = [];

  /**
   * @var \Civi\Share\CiviMRF\CiviMRFClient
   * @inject civi.share.civimrf_client
   */
  protected $civiMRFClient;

  /**
   * @var \Civi\Share\CiviMRF\ShareApi
   * @inject civi.share.api
   */
  protected $shareApi;

  /**
   * @var \Civi\Core\CiviEventDispatcherInterface
   * @inject dispatcher
   */
  protected $eventDispatcher;

  public function __construct() {
    $this->civiMRFClient = Civi::service('civi.share.civimrf_client');
    $this->shareApi = Civi::service('civi.share.api');
    $this->eventDispatcher = Civi::service('dispatcher');
  }

  public function getEventDispatcher(): \Civi\Core\CiviEventDispatcherInterface {
    return $this->eventDispatcher;
  }

  public function setShareApi(ShareApi $shareApi): void {
    $this->shareApi = $shareApi;
  }

  public function setSenderNodeId(int $senderNodeId): void {
    $this->senderNodeId = $senderNodeId;
  }

  /**
   * @phpstan-param list<int> $targetNodePeeringIds
   */
  public function setTargetNodePeeringIds(array $targetNodePeeringIds): void {
    $this->targetNodePeeringIds = $targetNodePeeringIds;
  }

  /**
   * @phpstan-param array<string, array<string, mixed>> $entityIdentificationContext
   */
  public function setEntityIdentificationContext(array $entityIdentificationContext): void {
    $this->entityIdentificationContext = $entityIdentificationContext;
  }

  /**
   * @phpstan-return iterable<\Civi\Share\Message>
   */
  public static function generateForSourceNode(int $sourceNodeId): iterable {
    $shareChanges = ShareChange::get()
      ->addSelect(
        'id',
        'change_type',
        'local_contact_id',
        'source_node_id',
        'change_date',
        'received_date',
        'status',
        'entity_type',
        'data_before',
        'data_after'
      )
      ->addWhere('source_node_id', '=', $sourceNodeId)
      ->addWhere('status', 'IN', Change::PENDING_FROM_SENDING_STATUS)
      ->execute();

    foreach ($shareChanges as $shareChange) {
      $message = new self();
      $change = Change::createFromApiResultArray($shareChange);
      $message->addChange($change);

      // Determine target node peerings to send this message to and context for identification of entities, depending on
      // the change.
      $messageGenerateEvent = new MessageGenerateEvent($change);
      $message->getEventDispatcher()->dispatch(MessageGenerateEvent::class, $messageGenerateEvent);
      $message->setTargetNodePeeringIds($messageGenerateEvent->getEligibleTargetNodePeeringIds());
      $message->setEntityIdentificationContext($messageGenerateEvent->getEntityIdentificationContext());
      $message->setSenderNodeId($sourceNodeId);

      // TODO: As this currently creates one message per change record, this should be optimized by e. g. combining
      //       messages for the same target node peerings into one message.

      yield $message;
    }
  }

  public static function createFromSerializedMessage(
    array $serializedMessage,
    ?\DateTimeInterface $receivedDate = NULL
  ): self {
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
      ->addWhere('id', 'IN', $this->getPersistedChangeIds())
      ->execute();
  }

  public function sendToAll($localNodeId = NULL): array {
    // get the local node
    // TODO: cache?
    if (!isset($localNodeId)) {
      // first: get the source node, and then find nodes to send these changes to
      // TODO: What if there's multiple ones?
      $localNode = \Civi\Api4\ShareNode::get(TRUE)
        ->addSelect('is_local')
        ->addWhere('is_local', '=', TRUE)
        ->setLimit(1)
        ->execute()
        ->first();
      if (isset($localNode)) {
        $localNodeId = $localNode['id'];
      }

      if (!isset($localNodeId)) {
        throw new \RuntimeException('CiviShare: Could not find local source node for sending change message.');
      }
    }

    // Add all enabled node peerings for this local node.
    $this->targetNodePeeringIds = ShareNodePeering::get(FALSE)
      ->addSelect('id', 'remote_node', 'remote_node.*')
      ->addWhere('local_node', '=', $localNodeId)
      ->addWhere('is_enabled', '=', TRUE)
      ->execute()
      ->column('id');

    $this->send();
  }

  /**
   * Send out a compiled message
   *
   * @return list<array{node_peering_id:int, result:array<string, mixed>}>
   */
  public function send(): array {
    // TODO: Is 'data' the right type?
    $lock = \Civi::lockManager()->acquire('data.civishare.changes');

    if (0 === count($this->targetNodePeeringIds)) {
      // no peered instances: mark changes as DROPPED
      $this->markChanges(Change::STATUS_DROPPED);
    }

    $sendResult = [];
    foreach ($this->targetNodePeeringIds as $peeringId) {
      $apiResult = $this->shareApi->sendMessage($peeringId, $this);
      foreach ($this->changes as $change) {
        $change->setStatus(Change::STATUS_DONE);
        $change->persist();
      }

      $sendResult[] = [
        'node_peering_id' => $peeringId,
        'result' => $apiResult ?? NULL,
      ];
    }

    $lock->release();
    return $sendResult;
  }

  /**
   * Process the received and verified message on the given node
   *
   * @param int $local_node_id
   *   the node where this will be processed on
   *
   * @return void
   */
  public function processChanges($local_node_id) {
    // is 'data' the right type?
    $lock = \Civi::lockManager()->acquire('data.civishare.changes');
    if (!$lock->isAcquired()) {
      throw new \RuntimeException('CiviShare: Could not acquire lock for processing changes.');
    }

    // load the changes
    $changes = \civicrm_api4('ShareChange', 'get', [
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
        }
        else {
          $this->setChangeStatus($change['id'], $change_processor->getNewStatus() ?? Change::STATUS_ERROR);
          \Civi::log()->warning("Change [{$change['id']}] could not be processed - no processor found.");
        }
      }
      catch (\RuntimeException $exception) {
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
  public function setChangeStatus($change_id, $status) {
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
  public function processOnNode($local_node_id) {
    // is 'data' the right type?
    $lock = \Civi::lockManager()->acquire('data.civishare.changes');

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

  /**
   * Apply the given change by extracting the relevant change handler
   *
   * @param array $change
   *    change data
   *
   * @return void
   */
  public function applyChange(array $change) {
    // @todo rename handler_class to change_type
    $change_type = $change['handler_class'];

    // @todo implement
  }

  /**
   * Check if all changes in the message have been processed
   *
   * @return boolean
   */
  public function allChangesProcessed() {
    // load the changes
    $changes = civicrm_api4('ShareChange', 'get', [
      'where' => [
        ['id', 'IN', $this->getPersistedChangeIds()],
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
   * @param int $node_peering_id
   *   the ID of an active peering object
   *
   * @return ?Message
   */
  public static function extractSerialisedMessage($data, $node_peering_id) : ?Message {
    // todo: implement
    throw new \Exception('not implemented');
    return NULL;
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
      ],
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
