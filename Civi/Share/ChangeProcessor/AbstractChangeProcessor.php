<?php

declare(strict_types = 1);

namespace Civi\Share\ChangeProcessor;

use Civi\Core\Event\GenericHookEvent as Event;
use Civi\Api4\ShareChange;
use Civi\Api4\ShareNode;
use Civi\Share\Change;
use Civi\Share\ChangeProcessingEvent;

/**
 * Abstract class providing infrastructure for change processors
 */
abstract class AbstractChangeProcessor {

  public const CHANGE_TYPE_CONTACT_BASE = 'civishare.change.contact.base';

  /**
   * This method will be called when a change is offered to this processor for
   * processing
   *
   * @param \Civi\Share\ChangeProcessingEvent $processing_event
   *   the event provides data and infrastructure
   *
   * @param string $event_type
   *   make sure this is an event type you actually *can* process
   */
  public function process_change_event($processing_event, $event_type, $dispatcher) {
    // we don't want already processed events
    if ($processing_event->isProcessed()) {
      return;
    }

    // we want to make sure it's one of our event types
    if (!in_array($event_type, $this->event_types)) {
      return;
    }

    // looks good -> pass to the implementation
    try {
      $this->process_change($processing_event, $event_type, $dispatcher);
    }
    catch (\Exception $ex) {
      $processing_event->setNewChangeStatus(Change::STATUS_ERROR);
    }
  }

  /**
   * This method will be called when a change is offered to this processor for
   * processing
   *
   * @param \Civi\Share\ChangeProcessingEvent $processing_event
   *   the event provides data and infrastructure
   *
   * @param string $event_type
   *   make sure this is an event type you actually *can* process
   */
  abstract public function process_change($processing_event, $event_type, $dispatcher);

  /**
   * Get a configuration option from the processor's/handler's configuration
   *
   * @param string $path
   *   path to the configuration option to return, separated by '/'
   *
   * @param mixed $defalt_value
   *   this value will be returned if the key is not found in the settings
   *
   * @return mixed
   */
  public function getConfigValue($path, $defalt_value): mixed {
    // @todo implement!
    \Civi::log()
      ->debug('ChangeProcessor::getConfigValue needs to be implmented.');
    return $defalt_value;
  }

  /**
   * Log messages in order to trace/debug change processing
   *
   * @param $node_id
   * @param $change_id
   * @param $message
   *
   * @return void
   */
  public function log($node_id, $change_id, $message) {
    $processor_short_name = $this->getShortName();
    // @todo: create a separate log?
    \Civi::log()
      ->debug("[{$processor_short_name}|#N{$node_id}|C{$change_id}]: " . $message);
  }

  /**
   * Return a short name, mostly used for logging
   *
   * @return string
   */
  public function getShortName() {
    return get_class($this);
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
  public function register($change_types, $weight = ChangeProcessingEvent::DEFAULT_PROCESSING) {
    $result = \Civi::dispatcher()->addListener(
      ChangeProcessingEvent::NAME,
      [$this, 'process_change'],
      $weight
    );
  }

  /**
   * Get the parameter set for the requested attributes
   *
   * @param array $attribute_set
   * @param array $data
   *
   * @return array
   */
  public function buildSearchParameters($attribute_set, $data, $fallbackData) {
    $query = [];
    foreach ($attribute_set as $attribute) {
      if (isset($data[$attribute])) {
        $query[$attribute] = $data[$attribute];
      }
    }
    if ([] === $query) {
      foreach ($attribute_set as $attribute) {
        if (isset($fallbackData[$attribute])) {
          $query[$attribute] = $fallbackData[$attribute];
        }
      }
    }
    return $query;
  }

}
