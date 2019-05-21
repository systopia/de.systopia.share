<?php
/*-------------------------------------------------------+
| CiviShare                                              |
| Copyright (C) 2019 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*/

/**
 * Change Record Interface
 *
 * @todo turn into CiviCRM entity
 */
class CRM_Share_Change {

  protected $change_id   = NULL;
  protected $change_data = NULL;

  public function __construct($id) {
    $this->change_id   = (int) $id;
    $this->change_data = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_share_change WHERE id = {$this->change_id};");
    if (!$this->change_data->fetch()) {
      throw Exception("Change [{$this->change_id}] not found.");
    }
  }

  /**
   * Get the value of a field
   *
   * @param $key string field name
   * @return mixed value
   */
  public function get($key) {
    if (isset($this->change_data->$key)) {
      return $this->change_data->$key;
    } else {
      return NULL;
    }
  }

  /**
   * Get the parsed JSON value of a field
   *  - even if encoded twice
   *
   * @param $key string field name
   * @return mixed value
   */
  public function getJSONData($key) {
    return json_decode($this->get($key), TRUE);
  }

  /**
   * Get the list of local fields of the change 'entity'
   */
  public static function getLocalFields() {
    return ['id', 'status', 'received_date', 'processed_date', 'local_contact_id', 'source_node_id'];
  }

  /**
   * Get the list of global fields of the change 'entity'
   */
  public static function getGlobalFields() {
    return ['change_id', 'change_group_id', 'hash', 'handler_class', 'change_date', 'data_before', 'data_after'];
  }

  /**
   * Get the contact ID this change is related to
   * @return int contact ID
   */
  public function getContactID() {
    return $this->get('local_contact_id');
  }

  /**
   * Apply the change to the database
   *
   * @return boolean TRUE if anything was changed, FALSE if not
   * @throws Exception should there be a problem
   */
  public function apply() {
    // get the handler
    $handler = CRM_Share_Controller::singleton()->getHandler($this);
    if (!$handler) {
      throw new Exception("Unknown handler: '{$this->get('handler_class')}'. Not applied!");
    }

    // apply the change
    return $handler->apply($this);
  }

  /**
   * Set the status of this change to the given value
   */
  public function setStatus($status) {
    // TODO: filter for valid statuses?
    $this->change_data->status = $status;
    CRM_Core_DAO::executeQuery("UPDATE civicrm_share_change SET status = %1 WHERE id = %2", [
        1 => [$status,          'String'],
        2 => [$this->get('id'), 'Integer']]);
  }

  /**
   * Serialise the change data
   * @return array all global fields
   */
  public function toArray() {
    $change_data = [
        'contact_id' => $this->getContactID()
    ];
    foreach (self::getGlobalFields() as $field) {
      $change_data[$field] = $this->get($field);
    }
    return $change_data;
  }

  /**
   * Get a list of all change objects that are in the same group as this one,
   *  including this one
   * @param $status array|string (list of) status to consider
   * @return array list of CRM_Share_Change objects
   */
  public function getAllChangesOfThatGroup($status = NULL) {
    $changes = [];
    $change_group_id = $this->get('change_group_id');
    if (empty($change_group_id)) {
      // this change is not grouped
      $changes[] = $this;

    } else {
      // this is, potentially, a grouped change
      $status_clause = self::buildStatusClause($status);

      // build query
      $changes_query = CRM_Core_DAO::executeQuery("
      SELECT id AS change_id
      FROM civicrm_share_change
      WHERE {$status_clause}
        AND change_group_id = %1
      ORDER BY received_date ASC", [1 => [$change_group_id, 'String']]);

      // build change list
      while ($changes_query->fetch()) {
        $change_id = (int) $changes_query->change_id;
        if ($change_id == $this->change_id) {
          $changes[] = $this;
        } else {
          $changes[] = new CRM_Share_Change($change_id);
        }
      }
    }
    return $changes;
  }

  /**
   * Get an SQL status filter clause based on the status list
   *
   * @param $status array|string (list of) status to consider
   * @return string SQL clause
   */
  protected static function buildStatusClause($status) {
    if (is_string($status)) {
      $status = [$status];
    }
    if (empty($status)) {
      $status_clause = 'TRUE';
    } else {
      $status_clause = "status IN ('" . implode("','", $status) . "')";
    }
    return$status_clause;
  }

  /**
   * Get a change object by the globally valid change ID
   *
   * @param $change_id string change ID
   * @return CRM_Share_Change|null change object, if it exists
   */
  public static function getByChangeID($change_id) {
    $internal_change_id = CRM_Core_DAO::singleValueQuery("SELECT * FROM civicrm_share_change WHERE change_id = %1;", [1 => [$change_id, 'String']]);
    if ($internal_change_id) {
      return new CRM_Share_Change($internal_change_id);
    } else {
      return NULL;
    }
  }

  /**
   * Get the oldest next change with the given status
   *
   * @param $status array|string (list of) status to consider
   * @return CRM_Share_Change|null change object
   */
  public static function getNextChangeWithStatus($status) {
    $status_clause = self::buildStatusClause($status);

    // build query
    $change_id = CRM_Core_DAO::singleValueQuery("
      SELECT id
      FROM civicrm_share_change
      WHERE {$status_clause}
      ORDER BY change_date ASC
      LIMIT 1");
    if ($change_id) {
      return new CRM_Share_Change($change_id);
    } else {
      return NULL;
    }
  }

  /**
   * Create/insert a new change entry
   *
   * @param $change_id        string unique change ID
   * @param $handler_class    string handler class
   * @param $source_node_id   int source node ID (in civicrm_share_node)
   * @param $data_before      array data/object
   * @param $data_after       array data/object
   * @param $change_date      string date/timestamp
   *
   * @return CRM_Share_Change the newly created change
   */
  public static function createNewChangeRecord($change_id, $handler_class, $source_node_id, $data_before, $data_after, $change_date, $local_contact_id) {
    $lock = CRM_Share_Controller::singleton()->getChangesLock();

    $data_before_string = json_encode($data_before);
    $data_after_string  = json_encode($data_after);
    $change_hash        = sha1($data_before_string . $data_after_string);
    $change_date_string = date('YmdHis', strtotime($change_date));
    $status             = $source_node_id == CRM_Share_Configuration::getLocalNodeID() ? 'LOCAL' : 'PENDING';

    CRM_Core_DAO::executeQuery("
    INSERT INTO civicrm_share_change (change_id, hash, handler_class, source_node_id, data_before, data_after, change_date, received_date, status, local_contact_id)
                               VALUES(%1, %2, %3, %4, %5, %6, %7, NOW(), %8, %9);", [
                                   1 => [$change_id, 'String'],
                                   2 => [$change_hash, 'String'],
                                   3 => [$handler_class, 'String'],
                                   4 => [$source_node_id, 'Integer'],
                                   5 => [$data_before_string, 'String'],
                                   6 => [$data_after_string, 'String'],
                                   7 => [$change_date_string, 'String'],
                                   8 => [$status, 'String'],
                                   9 => [$local_contact_id, 'String']
    ]);

    $internal_change_id = CRM_CORE_DAO::singleValueQuery('SELECT LAST_INSERT_ID()');
    CRM_Share_Controller::singleton()->newChangeStored($change_id);
    CRM_Share_Controller::singleton()->releaseLock($lock);
    return new CRM_Share_Change($internal_change_id);
  }

  /**
   * Verify and store a change record received from another node
   *
   * @param $remote_node        CRM_Share_Node sender node
   * @param $serialised_change  array          serialised node
   * @return boolean  TRUE iff the change was accepted and stored
   */
  public static function storeChange($remote_node, $serialised_change) {

    // FIRST: verify that the change is valid
    // 0. check if it has all the necessary attributes
    foreach (self::getGlobalFields() as $field) {
      if ($field !== 'change_group_id') { // change group is optional
        if (empty($serialised_change[$field])) {
          CRM_Share_Controller::singleton()->log("Change '{$serialised_change['change_id']}' rejected. Field {$field} is not set.", 'warn');
          return false;
        }
      }
    }

    // 1. check if too old (longer than retention time)
    $change_date        = strtotime($serialised_change['change_date']);
    $change_date_cutoff = strtotime("now - " . CRM_Share_Configuration::getRetentionTime());
    if ($change_date < $change_date_cutoff) {
      CRM_Share_Controller::singleton()->log("Change '{$serialised_change['change_id']}' rejected. Retention time exceeded.", 'warn');
      return FALSE;
    }

    // 2. check path
    // TODO: implement paths (i.e. where did it come from)

    // 3. change is not already in the DB
    $existing_change = CRM_Share_Change::getByChangeID($serialised_change['change_id']);
    if ($existing_change) {
      CRM_Share_Controller::singleton()->log("Change '{$serialised_change['change_id']}' rejected. Already exists in DB.");
      return TRUE; // don't treat as an error
    }

    // 4. check if active, peered contact exists
    $peering = new CRM_Share_Peering($remote_node);
    $contact_id = $peering->getLocalPeer($serialised_change['contact_id']);
    if (!$contact_id) {
      CRM_Share_Controller::singleton()->log("Change '{$serialised_change['change_id']}' rejected. No peered contact found.");
      return FALSE;
    }

    // 5. deserialise data fields
    foreach (['data_before', 'data_after'] as $field) {
      if (array_key_exists($field, $serialised_change)) {
        $deserialised_data = json_decode($serialised_change[$field], TRUE);
        if ($deserialised_data !== NULL) {
          $serialised_change[$field] = $deserialised_data;
        } else {
          $serialised_change[$field] = '';
        }
      }
    }

    // ALL GOOD -> store
    self::createNewChangeRecord(
        $serialised_change['change_id'],
        $serialised_change['handler_class'],
        $remote_node->getID(),
        $serialised_change['data_before'],
        $serialised_change['data_after'],
        date('YmdHis', $change_date),
        $contact_id);

    // all is well
    CRM_Share_Controller::singleton()->log("Change '{$serialised_change['change_id']}' received.");
    return TRUE;
  }
}