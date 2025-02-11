<?php

namespace Civi\Share\Event;

use Civi\Api4\ShareNodePeering;
use Civi\Share\Change;
use Symfony\Contracts\EventDispatcher\Event;

class MessageGenerateEvent extends Event {

  private Change $change;

  /**
   * @phpstan-var list<int>
   */
  public array $targetNodePeeringIds = [];

  /**
   * @phpstan-var array<string, array<string, mixed>>
   */
  public array $entityIdentificationContext = [];

  /**
   * @phpstan-param list<int> $targetNodePeeringIds
   */
  public function __construct(Change $change, array $targetNodePeeringIds = []) {
    $this->change = $change;
    $this->targetNodePeeringIds = $targetNodePeeringIds;
  }

  public function getChange(): Change {
    return $this->change;
  }

  /**
   * @return list<int>
   */
  public function getTargetNodePeeringIds(): array {
    return $this->targetNodePeeringIds;
  }

  /**
   * @return array<string, array<string, mixed>>
   */
  public function getEntityIdentificationContext() {
    return $this->entityIdentificationContext;
  }

  /**
   * @return list<int>
   */
  public function getEligibleTargetNodePeeringIds(): array {
    return ShareNodePeering::get(FALSE)
      ->addSelect('id')
      ->addWhere('id', 'IN', $this->targetNodePeeringIds)
      ->addWhere('is_enabled', '=', TRUE)
      ->execute()
      ->column('id');
  }

}
