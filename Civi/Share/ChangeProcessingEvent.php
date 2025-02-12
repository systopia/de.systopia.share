<?php

declare(strict_types = 1);

namespace Civi\Share;

use Civi\Core\Event\GenericHookEvent as Event;

/**
 * CiviShare ChangeProcessingEvent
 *
 * The purpose of this class is to process/apply a set of changes
 *  to the system.
 *
 * It will trigger a symfony hook to determine which processor instances would
 *  want to process the change at hand an will make them apply it.
 *
 * Processors can have a look a the set of changes to determine whether they
 *  can process it.
 *
 * Provided by the CiviShare extension.
 */
class ChangeProcessingEvent extends Event {

  public const NAME = 'de.systopia.change.process';

  /**
   * ChangeProcessor priority PRIORITY: use if you definitely want the have a first go a this
   */
  public const PRIORITY_PROCESSING = 1000;

  /**
   * ChangeProcessor priority is very high: use to override the default processing
   */
  public const EARLY_PROCESSING = 750;

  /**
   * ChangeProcessor priority is high: use to override the default processing
   */
  public const PREFERRED_PROCESSING = 500;

  /**
   * ChangeProcessor priority is default
   */
  public const DEFAULT_PROCESSING = 250;

  /**
   * ChangeProcessor priority is low
   */
  public const LATE_PROCESSING = 100;

  /**
   * ChangeProcessor priority: after the regular processing
   */
  public const POST_PROCESSING = 50;

  /**
   * ChangeProcessor priority REPORTING: at this point everything should've happened
   */
  public const REPORTING = 0;

  /**
   * @var boolean change_handlers_registered
   */
  protected static bool $configured_change_handlers_registered = FALSE;

  /**
   * @var int node ID
   */
  protected int $node_id;

  protected Change $change;

  /**
   * @var int change ID
   */
  protected int $change_id;

  /**
   * @var array node data
   */
  protected ?array $node_data = NULL;

  /**
   * @var array change data
   */
  protected ?array $change_data = NULL;

  /**
   * @var bool has this change been processed?
   */
  protected bool $is_processed = FALSE;

  /**
   * @var string|null the new status after the processing
   */
  protected ?string $new_change_status = NULL;

  /**
   * Create a new change processor for the given node. You can also add APIv4
   * data of the node if you have it.
   *
   * @param int $node_id
   *   ID of the node the changes should be processed on.
   *
   * @param array $change_data
   *   APIv4 data of the change, in case you have it - will otherwise be loaded
   *
   * @param array $node_data
   *   APIv4 data of the node, in case you have it - will otherwise be loaded
   */
  public function __construct($change_id, $node_id, $change_data = NULL, $node_data = NULL) {
    $this->node_id = $node_id;
    $this->change_id = $change_id;
    $this->is_processed = FALSE;
    $this->change_data = $change_data;
    $this->node_data = $node_data;
    $this->change = Change::createFromExisting($change_id);

    // load node data if not given
    if (empty($this->change_data)) {
      $this->change_data = \Civi\Api4\ShareChange::get(TRUE)
        ->addWhere('id', '=', $change_id)
        ->setLimit(1)
        ->execute()
        ->first();
    }

    // load node data if not given
    if (empty($this->node_data)) {
      $this->node_data = \Civi\Api4\ShareNode::get(TRUE)
        ->addWhere('id', '=', $node_id)
        ->setLimit(1)
        ->execute()
        ->first();
    }

    // register change handlers as defined in the database (ONCE)
    if (!self::$configured_change_handlers_registered) {
      $shareHandlers = \Civi\Api4\ShareHandler::get(TRUE)
        ->addSelect('id', 'name', 'class', 'weight', 'configuration')
        ->addWhere('is_enabled', '=', 1)
        ->addOrderBy('weight', 'ASC')
        ->execute();
      foreach ($shareHandlers as $shareHandler) {
        // todo: make sure you can't inject code here
        $handler = new $shareHandler['class']();
        \Civi::dispatcher()->addListener(
          self::NAME,
          [$handler, 'process_change'],
          $shareHandler['weight']
        );
      }
      self::$configured_change_handlers_registered = TRUE;
    }
  }

  /**
   * Has this change been procesed?
   *
   * @return bool
   */
  public function isProcessed() {
    return $this->is_processed;
  }

  /**
   * Has this change been procesed?
   */
  public function setProcessed($is_processed = TRUE) {
    $this->is_processed = $is_processed;
  }

  /**
   * Get the new status for the CHANGE object being processed
   *
   * @return string
   */
  public function getNewChangeStatus() {
    return $this->$new_change_status;
  }

  /**
   * Set the new status for the CHANGE object being processed
   *
   * @param string $status
   *
   * @return void
   */
  public function setNewChangeStatus($status) {
    $this->$new_change_status = $status;
  }

  /**
   * Check if the change being processed is the given type
   *
   * @param $change_type
   *
   * @return void
   */
  public function hasChangeType($change_type) {
    return $this->change_data['change_type'] == $change_type;
  }

  /**
   * Get the new status of the change as suggested by the change processor(s)
   *
   * @return string|null
   */
  public function getNewStatus(): ?string {
    return $this->new_change_status;
  }

  /**
   * Will try to use the local_contact_id from the receieved change to look up
   * the corresponding local contact using the peering service
   *
   * @return ?int
   */
  public function getLocalContactID() {
    $submitted_contact_id = $this->getRemoteContactID();
    if (NULL === $submitted_contact_id) {
      return NULL;
    }

    // use peering to look up local contact
    // @todo migrate peering to service
    $peering = new \Civi\Share\IdentityTrackerContactPeering();
    $local_contact_id = $peering->getLocalContactId($submitted_contact_id, $this->change->getSourceNodeId());

    if (empty($local_contact_id)) {
      // isn't peered
      return NULL;
    }
    else {
      return (int) $local_contact_id;
    }
  }

  public function getChange(): Change {
    return $this->change;
  }

  /**
   * Get the attributes of the change entiti being processed right now
   *
   * @return array
   */
  public function getChangeData(): array {
    return $this->change_data;
  }

  /**
   * Will return the remote contact ID, as long as it's submitted.
   */
  public function getRemoteContactID(): ?int {
    return isset($this->change_data['local_contact_id'])
      ? (int) $this->change_data['local_contact_id']
      : NULL;
  }

  /**
   * Get the data AFTER the change
   *
   * @return ?int
   */
  public function getContactID() {
    // use

    $before_data_contact_id = $this->getChangeDataBefore()['contact_id'] ?? NULL;
    $after_data_contact_id = $this->getChangeDataAfter()['contact_id'] ?? NULL;
    if ($before_data_contact_id && $after_data_contact_id && $before_data_contact_id != $after_data_contact_id) {
      // todo: log as contact_id conflict
      // there is a conflict
      return NULL;
    }
    else {
      return $after_data_contact_id ?? $before_data_contact_id ?? NULL;
    }
  }

  /**
   * Get the data BEFORE the change
   *
   * @return array
   */
  public function getChangeDataBefore() {
    // todo: cache? might be tricky...
    return $this->change_data['data_before'];
  }

  /**
   * Get the data AFTER the change
   *
   * @return array
   */
  public function getChangeDataAfter() {
    // todo: cache? might be tricky...
    return $this->change_data['data_after'];
  }

  /**
   * Log a message from change processing
   *
   * @param string $message
   *
   * @return void
   */
  public function logProcessingMessage($message) {
    \Civi::log("[CHANGE#{$this->change_id}] " . $message);
  }

  /**
   * Helper to deserialise JSON data in the Change object
   *
   * @param string $serialised_data
   *
   * @return array
   */
  protected function getJsonData($serialised_data) {
    $data = json_decode($serialised_data, TRUE);
    // todo: error handling
    return $data;
  }

  /**
   * Get the entity_identification_context from this change
   *
   * @param string $context
   *   context prefix
   *
   * @return array
   *    the data with the prefix $context
   */
  public function getEntityIdentificationContext(array $context) : array
  {
    return $this->change_data['entity_identification_context'] ?? [];

  }

}
