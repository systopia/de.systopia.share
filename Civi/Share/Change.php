<?php

namespace Civi\Share;

use Civi\Api4\ShareChange;

class Change {

  public const STATUS_LOCAL = 'LOCAL';

  protected ?int $id = NULL;

  protected string $type;

  protected \DateTimeInterface $changedDate;

  protected \DateTimeInterface $receivedDate;

  protected string $entity;

  protected int $entityId;

  protected array $attributeChanges;

  protected int $sourceNodeId;

  public function __construct(
    string $type,
    string $entity,
    string $entityId,
    int $sourceNodeId,
    array $attributeChanges = [],
    ?\DateTime $changedDate = NULL,
    ?\DateTime $receivedDate = NULL,
    ?int $id = NULL
  ) {
    $this->type = $type;
    $this->entity = $entity;
    $this->entityId = $entityId;
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
          'entity',
          'entity_reference',
          'attribute_changes',
          'timestamp',
        ],
        array_keys($serializedChange),
      ) !== []) {
      throw new \RuntimeException('Could not parse change.');
    }
    return new self(
      $serializedChange['type'],
      $serializedChange['entity'],
      $serializedChange['entity_reference'],
      $sourceNodeId,
      $serializedChange['attribute_changes'],
      \DateTime::createFromFormat(Utils::DATE_FORMAT, $serializedChange['timestamp']),
      $receivedDate ?? new \DateTime(),
    );
  }

  public static function createFromExisting(int $id): self {
    $shareChange = ShareChange::get()
      ->addSelect('id', 'type', 'entity', 'entity_reference', 'source_node_id', 'date', 'data_before', 'data_after')
      ->addWhere('id', '=', $id)
      ->execute()
      ->single();
    return new self(
      $shareChange['type'],
      $shareChange['entity'],
      $shareChange['entity_reference'],
      $shareChange['source_node_id'],
      self::parseAttributeChanges($shareChange['data_before'], $shareChange['data_after']),
      $shareChange['change_date'],
      $shareChange['received_date'],
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
      ->addValue('type', $this->type)
      ->addValue('entity', $this->entity)
      ->addValue('entity_reference', $this->entityId)
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
