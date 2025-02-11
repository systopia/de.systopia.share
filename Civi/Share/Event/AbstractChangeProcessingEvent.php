<?php

namespace Civi\Share\Event;

use Civi\Api4\ShareNodePeering;
use Civi\Share\Change;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This Symfony event will be used in the context of change handling
 */
class AbstractChangeProcessingEvent extends Event {

  /**
   * @var Change the change this peering event is used for
   */
  private Change $change;

  public function __construct(Change $change) {
    $this->change = $change;
  }

  public function getChange() {
    return $this->change;
  }
}
