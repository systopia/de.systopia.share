<?php

namespace Civi\Share\Event;

use Civi\Api4\ShareNodePeering;
use Civi\Share\Change;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event can manipulate the parameters sent along with a given change.
 *   It can be used to post additional parameters to facilitate identification
 */
class ModifyContextParametersEvent extends AbstractChangeProcessingEvent {

  /**
   * @phpstan-var list<int>
   */
  public array $context_parameters = [];


  public function __construct(Change $change, array $context_parameters = []) {
    parent::__construct($change);
    $this->$context_parameters = $context_parameters;
  }

  public function getContextParameters(): array {
    return $this->context_parameters;
  }

  public function setContextParameters(array $context_parameters) : void
  {
    $this->context_parameters = $context_parameters;
  }

  public function setContextParameter(string $name, string $value) : void
  {
    $this->context_parameters[$name] = $value;
  }
}
