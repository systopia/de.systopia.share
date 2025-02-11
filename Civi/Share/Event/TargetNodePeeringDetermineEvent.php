<?php

namespace Civi\Share\Event;

use Civi\Api4\ShareNodePeering;
use Civi\Share\Change;
use Symfony\Contracts\EventDispatcher\Event;

class TargetNodePeeringDetermineEvent extends AbstractChangeProcessingEvent {

  /**
   * @phpstan-var list<int>
   */
  public array $targetNodePeeringIds = [];

  public function __construct(Change $change, array $targetNodePeeringIds = []) {
    parent::__construct($change);
    $this->targetNodePeeringIds = $targetNodePeeringIds;
  }

  public function getTargetNodePeeringIds() : array {
    return $this->targetNodePeeringIds;
  }

  public function getEligibleTargetNodePeeringIds() {
    return ShareNodePeering::get(FALSE)
      ->addSelect('id')
      ->addWhere('id', 'IN', $this->targetNodePeeringIds)
      ->addWhere('is_enabled', '=', TRUE)
      ->execute()
      ->column('id');
  }

}
