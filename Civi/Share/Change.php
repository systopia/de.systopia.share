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
   *   This change is currently being worked on, and should be left alone from
   *   other processes.
   */
  public const STATUS_BUSY = 'BUSY';

  /**
   * @var string
   *   This change has been recorded locally, and should be sent out.
   */
  public const STATUS_FORWARD = 'FORWARD';

  /**
   * @var string
   *   This change has been processed.
   */
  public const STATUS_DONE = 'DONE';

  /**
   * @var string
   *   This change has been recorded locally, and should be sent out.
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
    ?int $id = NULL
  ) {
    $this->type = $type;
    $this->localContactId = $localContactId;
    $this->sourceNodeId = $sourceNodeId;
    $this->attributeChanges = $attributeChanges;
    $this->changedDate = $changedDate ?? new \DateTime();
    $this->receivedDate = $receivedDate ?? new \DateTime();
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
    );
  }

  public static function createFromExisting(int $id): self {
    $shareChange = ShareChange::get()
      ->addSelect('id', 'change_type', 'local_contact_id', 'source_node_id', 'change_date', 'received_date', 'data_before', 'data_after')
      ->addWhere('id', '=', $id)
      ->execute()
      ->single();
    return new self(
      $shareChange['change_type'],
      $shareChange['local_contact_id'],
      $shareChange['source_node_id'],
      self::parseAttributeChanges($shareChange['data_before'] ?? [], $shareChange['data_after'] ?? []),
      \DateTime::createFromFormat(Utils::CIVICRM_DATE_FORMAT, $shareChange['change_date']),
      \DateTime::createFromFormat(Utils::CIVICRM_DATE_FORMAT, $shareChange['received_date']),
      $id
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

  public function persist(): void {
    $shareChangeQuery = ShareChange::create()
      ->addValue('change_type', $this->type)
      ->addValue('local_contact_id', $this->localContactId)
      ->addValue('data_before', $this->getDataBefore())
      ->addValue('data_after', $this->getDataAfter())
      ->addValue('change_date', $this->changedDate->format(Utils::DATE_FORMAT))
      ->addValue('received_date', $this->receivedDate->format(Utils::DATE_FORMAT))
      ->addValue('source_node_id', $this->sourceNodeId);
    if ($this->isPersisted()) {
      $shareChangeQuery
        ->addValue('id', $this->id);
    }
    $shareChange = $shareChangeQuery->execute();
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

}
