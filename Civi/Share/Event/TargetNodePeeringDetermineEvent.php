<?php

namespace Civi\Share\Event;

use Civi\Share\Change;
use Symfony\Contracts\EventDispatcher\Event;

class TargetNodePeeringDetermineEvent extends Event {

  private Change $change;

  /**
   * @phpstan-var list<int>
   */
  public array $targetNodePeeringIds = [];

  public function __construct(Change $change, array $targetNodePeeringIds = []) {
    $this->change = $change;
    $this->targetNodePeeringIds = $targetNodePeeringIds;
  }

  public function getChange() {
    return $this->change;
  }

  public function getTargetNodePeeringIds() {
    return $this->targetNodePeeringIds;
  }

}
