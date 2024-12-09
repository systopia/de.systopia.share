<?php
namespace Civi\Share\ChangeProcessor;

use Civi\Core\Event\GenericHookEvent as Event;
use Civi\Api4\ShareChange;
use Civi\Api4\ShareNode;
use Civi\Share\ChangeProcessingEvent;

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
   * @param string $ch
   *   make sure this is an event type you actually *can* process
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
      $processing_event->setNewChangeStatus(Change::STATUS_ERROR);
    }
  }

    /**
     * Return a short name, mostly used for logging
     *
     * @return string
     */
  public function getShortName()
  {
    return get_class($this);
  }

    /**
     * Log messages in order to trace/debug change processing
     *
     * @param $node_id
     * @param $change_id
     * @param $message
     * @return void
     */
  public function log($node_id, $change_id, $message)
  {
      $processor_short_name = $this->getShortName();
      // @todo: create a separate log?
      \Civi::log()->debug("[{$processor_short_name}|#N{$node_id}|C{$change_id}]: " . $message);
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

