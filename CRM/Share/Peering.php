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
 * Contains the peering algorithm with a given remote host
 */
class CRM_Share_Peering {

  protected $remote_node = NULL;

  /**
   * Create a new peering object
   * @param $remote_node CRM_Share_Node remote node
   */
  public function __construct($remote_node) {
    $this->remote_node = $remote_node;
  }

  /**
   * Try to peer the given contact
   *
   * @param $remote_contact_id   int   (remote) contact ID
   * @param $remote_contact_data array data of the remote contact
   *
   * @return string|int local contact ID if peering was successful, or:
   *                         'INSUFFICIENT_DATA' if there is not enough data submitted
   *                         'NOT_IDENTIFIED'    if no contact could be identified
   *                         'AMBIGUOUS'         if multiple contacts were identified
   *                         'ERROR'             if some unforseen error has occured
   */
  public function peer($native_contact_id, $remote_contact_data) {
    // first: see if this contact is already peered
    $local_contact_id = $this->getLocalPeer($native_contact_id);
    if ($local_contact_id) {
      return $local_contact_id;
    }

    // if not: identify contact
    $result = $this->identifyContact($remote_contact_data);
    if (is_int($result)) {
      // contact peer identified. Write record
      $this->createLink($result, $remote_contact_id);
    }
    return $result;
  }

  /**
   * Will try to identify/match a contact uniquely based on
   * the data offered
   *
   * @param $data array contact data offered
   * @return string|int local contact ID if matched, or:
   *                         'INSUFFICIENT_DATA' if there is not enough data submitted
   *                         'NOT_IDENTIFIED'    if no contact could be identified
   *                         'AMBIGUOUS'         if multiple contacts were identified
   *                         'ERROR'             if some unforseen error has occured
   *
   * @todo make behaviour configurable
   */
  public function identifyContact($data) {
    $attributes = ['contact_type', 'first_name', 'last_name', 'birth_date'];
    // this is a quick-and-dirty implementation
    if (empty($data['contact_type'])) {
      $data['contact_type'] = 'Individual';
    }

    // check if all attributes are there
    foreach ($attributes as $attribute) {
      if (empty($data[$attribute])) {
        return 'INSUFFICIENT_DATA';
      }
    }

    // find all matching contacts
    $data['check_permissions'] = 0;
    $data['return'] = 'id';
    try {
      $matches = civicrm_api3('Contact', 'get', $data);
      switch ($matches['count']) {
        case 0:
          return 'NOT_IDENTIFIED';
        case 1:
          return $matches['id'];
        default:
          return 'AMBIGUOUS';
      }
    } catch(Exception $ex) {
      $remote_contact_id = $this->remote_node->getRemoteContactID($native_contact_id);
      CRM_Share_Controller::singleton()->log("Peering of [{$remote_contact_id}] failed: " . $ex->getMessage());
      return 'ERROR';
    }
  }


  /**
   * Get the local contact for the peer ID
   *
   * @param $remote_contact_id int|null local contact ID or
   */
  public function getLocalPeer($remote_contact_id, $only_enabled = FALSE) {
    $remote_contact_id = $this->remote_node->getRemoteContactID($native_contact_id);

    $peer = CRM_Core_DAO::executeQuery("
    SELECT
        link.entity_id     AS contact_id,
        contact.is_deleted AS is_deleted,
        link.is_enabled    AS is_enabled
    FROM civicrm_value_share_link link
    LEFT JOIN civicrm_contact contact ON link.entity_id = contact.id
    WHERE link.civishare_id = %1", [1 => [$remote_contact_id, 'String']]);
    if ($peer->fetch()) {
      if ($peer->is_deleted) {
        // TODO: what to do with deleted, peered contacts?
      }
      HERE
    } else {
      return NULL;
    }
  }

  /**
   * Create a new link entry
   *
   * @param $local_contact_id   int the local contact ID
   * @param $native_contact_id  int the remote (native) contact ID
   * @throws CiviCRM_API3_Exception
   */
  public function createLink($local_contact_id, $native_contact_id) {
    $record = [
        'entity_id' => $local_contact_id,
        'entity_table' => 'civicrm_contact'];

    $record_data = [
        'civishare_link.civishare_node'       => $this->remote_node->getID(),
        'civishare_link.civishare_id'         => $this->reemote_node->getRemoteContactID($native_contact_id),
        'civishare_link.civishare_timestamp'  => date('YmdHis'),
        'civishare_link.civishare_is_enabled' => 1];
    CRM_Share_CustomData::resolveCustomFields($record_data);

    // merge data into record
    foreach ($record_data as $key => $value) {
      $record["{$key}:-1"] = $value;
    }

    civicrm_api3('CustomValue', 'create', $record);
  }
}