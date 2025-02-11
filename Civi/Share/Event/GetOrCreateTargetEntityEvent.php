<?php

namespace Civi\Share\Event;

use Civi\Api4\ShareNodePeering;
use Civi\Share\Change;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event will identify the given entity (e.g. mem)
 */
class GetOrCreateTargetEntityEvent extends AbstractChangeProcessingEvent {

  /**
   * @var ?string name of the entity type
   */
  protected ?string $entity_type = null;

  public function getEntityType($fallback = null) : ?string
  {
    return $this->entity_type;
  }

  /**
   * @phpstan-var list<int>
   */
  public array $context_parameters = [];


  public function __construct(Change $change, array $context_parameters = []) {
    parent::__construct($change);
    $this->$context_parameters = $context_parameters;
  }
}
