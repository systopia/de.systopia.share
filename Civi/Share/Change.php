<?php

namespace Civi\Share;

use Civi\Api4\ShareChange;

class Change {

  /**
   * @var string
   *   This change has been recorded locally, needs to be sent.
   */
  public const STATUS_LOCAL = 'LOCAL';

  /**
   * @var string
   *   This change is received, but has not been touched otherwise.
   */
  public const STATUS_PENDING = 'PENDING';

  /**
   * @var string
   *   This change has been received and is currently being worked on, and should be left alone from
   *   other processes.
   */
  public const STATUS_BUSY = 'BUSY';

  /**
   * @var string
   *   This change has been processed locally, and should be relayed.
   */
  public const STATUS_FORWARD = 'FORWARD';

  /**
   * @var string
   *   This change has been processed.
   */
  public const STATUS_DONE = 'DONE';

  /**
   * @var string
   *   This change was received and has been processed locally.
   */
  public const STATUS_PROCESSED = 'PROCESSED';

  /**
   * @var string
   *   This change has been recorded locally, and should be sent out
   */
  public const STATUS_DROPPED = 'DROPPED';

  /**
   * @var string
   *   This change has been recorded locally, and should be sent out.
   */
  public const STATUS_ERROR = 'ERROR';

  const ACTIVE_STATUS = [
    self::STATUS_LOCAL,
    self::STATUS_PENDING,
    self::STATUS_BUSY,
    self::STATUS_FORWARD,
  ];

  const COMPLETED_STATUS = [
    self::STATUS_DONE,
    self::STATUS_DROPPED,
    self::STATUS_ERROR,
    self::STATUS_PROCESSED,
  ];

  const PENDING_FROM_SENDING_STATUS = [
    self::STATUS_LOCAL,
    self::STATUS_FORWARD,
  ];

  /**
   * TESTING ONLY
   */
  public const CHANGE_TYPE_TEST = 'civishare.change.test';

  /**
   * Contact base data, no linked entities (like email).
   */
  public const CHANGE_TYPE_CONTACT_BASE = 'civishare.change.contact.base';

  protected ?int $id = NULL;

  protected string $type;

  protected string $status;

  protected \DateTimeInterface $changedDate;

  protected \DateTimeInterface $receivedDate;

  protected int $localContactId;

  protected array $attributeChanges;

  protected int $sourceNodeId;

  public function __construct(
    string $type,
    string $localContactId,
    int $sourceNodeId,
    array $attributeChanges = [],
    ?\DateTime $changedDate = NULL,
    ?\DateTime $receivedDate = NULL,
    string $status = self::STATUS_LOCAL,
    ?int $id = NULL
  ) {
    $this->type = $type;
    $this->localContactId = $localContactId;
    $this->sourceNodeId = $sourceNodeId;
    $this->attributeChanges = $attributeChanges;
    $this->changedDate = $changedDate ?? new \DateTime();
    $this->receivedDate = $receivedDate ?? new \DateTime();
    $this->status = $status;
    $this->id = $id;
  }

  public static function createFromSerialized(array $serializedChange, int $sourceNodeId, ?\DateTimeInterface $receivedDate = NULL): self {
    if (array_diff(
        [
          'type',
          'local_contact_id',
          'attribute_changes',
          'timestamp',
        ],
        array_keys($serializedChange),
      ) !== []) {
      throw new \RuntimeException('Could not parse change.');
    }
    return new self(
      $serializedChange['type'],
      $serializedChange['local_contact_id'],
      $sourceNodeId,
      $serializedChange['attribute_changes'],
      \DateTime::createFromFormat(Utils::DATE_FORMAT, $serializedChange['timestamp']),
      $receivedDate ?? new \DateTime(),
      Change::STATUS_PENDING
    );
  }

  public static function createFromExisting(int $id): self {
    $shareChange = ShareChange::get()
      ->addSelect('id', 'change_type', 'local_contact_id', 'source_node_id', 'change_date', 'status', 'received_date', 'data_before', 'data_after')
      ->addWhere('id', '=', $id)
      ->execute()
      ->single();
    return self::createFromApiResultArray($shareChange);
  }

  public static function createFromApiResultArray(array $shareChange): self {
    return new self(
      $shareChange['change_type'],
      $shareChange['local_contact_id'],
      $shareChange['source_node_id'],
      self::parseAttributeChanges($shareChange['data_before'] ?? [], $shareChange['data_after'] ?? []),
      \DateTime::createFromFormat(Utils::CIVICRM_DATE_FORMAT, $shareChange['change_date']),
      isset($shareChange['received_date']) ? \DateTime::createFromFormat(Utils::CIVICRM_DATE_FORMAT, $shareChange['received_date']) : NULL,
      $shareChange['status'],
      $shareChange['id']
    );
  }

  public function getId(): ?int {
    return $this->id;
  }

  public function isPersisted(): bool {
    return isset($this->id);
  }

  protected function getDataBefore(): array {
    $dataBefore = [];
    foreach ($this->attributeChanges as $attributeChange) {
      $dataBefore[$attributeChange['name']] = $attributeChange['from'];
    }
    return $dataBefore;
  }

  protected function getDataAfter(): array {
    $dataAfter = [];
    foreach ($this->attributeChanges as $attributeChange) {
      $dataAfter[$attributeChange['name']] = $attributeChange['to'];
    }
    return $dataAfter;
  }

  public function getSourceNodeId(): int {
    return $this->sourceNodeId;
  }

  public function setStatus(string $status): void {
    // TODO Validate.
    $this->status = $status;
  }

  public function process(int $localNodeId): void {
    $lock = \Civi::lockManager()
      // TODO: Is 'data' the right type?
      ->acquire('data.civishare.changes');
    if (!$lock->isAcquired()) {
      throw new \RuntimeException('CiviShare: Could not acquire lock for processing changes.');
    }

    if (!$this->isPersisted()) {
      throw new \RuntimeException('CiviShare: cannot process unpersisted changes.');
    }
    // TODO: Replace $change with instance of $this in ChangeProcessingEvent.
    $change = \Civi\Api4\ShareChange::get(TRUE)
      ->addWhere('id', '=', $this->id)
      ->setLimit(1)
      ->execute()
      ->first();
    $changeProcessingEvent = new \Civi\Share\ChangeProcessingEvent($this->id, $localNodeId, $change);
    try {
      \Civi::dispatcher()
        ->dispatch(ChangeProcessingEvent::NAME, $changeProcessingEvent);
      if ($changeProcessingEvent->isProcessed()) {
        $this->setStatus($changeProcessingEvent->getNewStatus() ?? self::STATUS_PROCESSED);
      }
      else {
        $this->setStatus($changeProcessingEvent->getNewStatus() ?? self::STATUS_ERROR);
        \Civi::log()
          ->warning("Change [{$this->id}] could not be processed - no processor found.");
      }
      $this->persist();
    }
    catch (\Exception $exception) {
      \Civi::log()
        ->warning("Change [{$this->id}] failed processing, exception was " . $exception->getMessage());
      $this->setStatus(self::STATUS_ERROR);
      $changeProcessingEvent->setFailed($exception->getMessage());
    }

    $lock->release();
  }

  public function persist(): void {
    $record = [
      'change_type' => $this->type,
      'local_contact_id' => $this->localContactId,
      'data_before' => $this->getDataBefore(),
      'data_after' => $this->getDataAfter(),
      'change_date' => $this->changedDate->format(Utils::DATE_FORMAT),
      'received_date' => $this->receivedDate->format(Utils::DATE_FORMAT),
      'status' => $this->status,
      'source_node_id' => $this->sourceNodeId,
    ];
    if ($this->isPersisted()) {
      $record['id'] = $this->id;
    }
    $shareChange = ShareChange::save()
      ->addRecord($record)
      ->setMatch(['id'])
      ->execute();

    $this->id = $shareChange['id'];
  }

  public static function parseAttributeChanges(array $dataBefore, array $dataAfter): array {
    $attributeChanges = [];
    foreach ($dataAfter as $attributeName => $valueAfter) {
      $attributeChanges[] = [
        'name' => $attributeName,
        'from' => $dataBefore[$attributeName] ?? NULL,
        'to' => $valueAfter,
      ];
    }
    return $attributeChanges;
  }

  public function serialize(): array {
    return [
      'type' => $this->type,
      'timestamp' => $this->changedDate->format(Utils::DATE_FORMAT),
      'local_contact_id' => $this->localContactId,
      'attribute_changes' => $this->attributeChanges,
    ];
  }

}
