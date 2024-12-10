<?php
namespace Civi\Share;

use Civi\Core\Event\GenericHookEvent as Event;
use Civi\Api4\ShareChange;
use Civi\Api4\ShareNode;

/**
 * Abstract class providing infrastructure for change processors
 */
abstract class ChangeProcessorBase
{
  /**
   * This method will be called when a change is offered to this processor for processing
   *
   * @param ChangeProcessingEvent $processing_event
   *   the event provides data and infrastructure
   *
   * @param string $event_type
   *   make sure this is an event type you actually *can* process
   */
  abstract function process_change($processing_event, $event_type, $dispatcher);

  /**
   * This method will be called when a change is offered to this processor for processing
   *
   * @param ChangeProcessingEvent $processing_event
   *   the event provides data and infrastructure
   *
   * @param string $event_type
   *   make sure this is an event type you actually *can* process
   */
  function process_change_event($processing_event, $event_type, $dispatcher) {
    // we don't want already processed events
    if ($processing_event->isProcessed()) return;

    // we want to make sure it's one of our event types
    if (!in_array($event_type, $this->event_types)) return;

    // looks good -> pass to the implementation
    try {
      $this->process_change($processing_event, $event_type, $dispatcher);
    } catch (\Exception $ex) {
      $processing_event->setNewChangeStatus(ShareChange::STATUS_ERROR);
    }
  }

  /**
   * Register this change processor
   *
   * @param array $change_types
   *   the change types this processor will act upon
   *
   * @param int $weight
   *   the processing priority
   */
  public function register($change_types, $weight = ChangeProcessingEvent::DEFAULT_PROCESSING)
  {
    $result = \Civi::dispatcher()->addListener(
      'de.systopia.change.process',
      [$this, 'process_change'],
      $weight
    );
  }
}

