<?php
* @deprecated will be handled differently, so this is no longer needed

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
 * This class covers both peering request directions:
 *  - outgoing (active), i.e. initiated by us
 *  - incoming (passive), i.e. initiated by remote node
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
   * Initiate a peering process to the remote node:
   *  1) gather identifying data on the given contact
   *  2) send REST API request to the other node
   *  3) process the results
   *
   * @param $contact_ids array list of local contact IDs to send
   * @return array result
   * @throws Exception if anything goes wrong
   */
  public function activePeer($contact_ids) {
    $reply = [
        'INSUFFICIENT_DATA' => 0,
        'NOT_IDENTIFIED'    => 0,
        'AMBIGUOUS'         => 0,
        'ERROR'             => 0,
        'NEWLY_PEERED'      => 0,
        'ALREADY_PEERED'    => 0,
    ];

    // 1) gather data on (yet unpeered contacts)
    $peered_ids = $this->getPeeredContactIDS($contact_ids);
    $reply['ALREADY_PEERED'] = count($peered_ids);
    $unpeered_ids = array_diff($contact_ids, $peered_ids);
    if (!empty($unpeered_ids)) {
      $peer_request = [
          'sender_key' => $this->remote_node->getKey(),
          'records'    => json_encode($this->getPeeringSignatures($unpeered_ids))
      ];

      // 2) send request
      $result = $this->remote_node->api3('CiviShare', 'peer', $peer_request);

      // 3 process results
      foreach ($result['values'] as $contact_id => $contact_result) {
        if (is_int($contact_result)) {
          // contact peer identified. Write record
          $this->createLink($contact_id, $contact_result);
          $reply['NEWLY_PEERED'] += 1;
        } else {
          $reply[$contact_result] += 1;
        }
      }
    }

    return $reply;
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
   *                         'ERROR'             if some unforeseen error has occurred
   */
  public function passivePeer($native_contact_id, $remote_contact_data) {
    // first: see if this contact is already peered
    $local_contact_id = $this->getLocalPeer($native_contact_id);
    if ($local_contact_id) {
      return $local_contact_id;
    }

    // if not: identify contact
    $result = $this->identifyContact($remote_contact_data);
    if (is_int($result)) {
      // contact peer identified. Write record
      $this->createLink($result, $native_contact_id);
    }
    return $result;
  }

  /**
   * Will try to identify/match a contact uniquely based on
   * the data offered
   *
   * WARNING: this is a simple, POC implementation
   *
   * @param $data array contact data offered
   * @return string|int local contact ID if matched, or:
   *                         'INSUFFICIENT_DATA' if there is not enough data submitted
   *                         'NOT_IDENTIFIED'    if no contact could be identified
   *                         'AMBIGUOUS'         if multiple contacts were identified
   *                         'ERROR'             if some unforseen error has occured
   *
   * @todo make behaviour configurable
   * @see getPeeringSignature call
   */
  public function identifyContact($data) {
    // TODO: configurable
    $attributes = ['contact_type', 'first_name', 'last_name', 'birth_date'];
    // this is a quick-and-dirty implementation
    if (empty($data['contact_type'])) {
      $data['contact_type'] = 'Individual';
    }

    // check if all attributes are there
    $query = [];
    foreach ($attributes as $attribute) {
      if (empty($data[$attribute])) {
        return 'INSUFFICIENT_DATA';
      } else {
        $query[$attribute] = $data[$attribute];
      }
    }

    // find all matching contacts
    $data['check_permissions'] = 0;
    $data['return'] = 'id';
    try {
      $matches = civicrm_api3('Contact', 'get', $query);
      switch ($matches['count']) {
        case 0:
          return 'NOT_IDENTIFIED';
        case 1:
          return $matches['id'];
        default:
          return 'AMBIGUOUS';
      }
    } catch(Exception $ex) {
      CRM_Share_Controller::singleton()->log("Peering failed, error while identifying contact: " . $ex->getMessage());
      return 'ERROR';
    }
  }


  /**
   * Get the local contact for the peer ID
   *
   * @param $remote_contact_id int|null local contact ID or
   */
  public function getLocalPeer($remote_native_contact_id, $only_enabled = FALSE) {
    $remote_contact_id = $this->remote_node->getShareContactID($remote_native_contact_id);

    $peer = CRM_Core_DAO::executeQuery("
    SELECT
        link.entity_id     AS contact_id,
        contact.is_deleted AS is_deleted,
        link.is_enabled    AS is_enabled
    FROM civicrm_value_share_link link
    LEFT JOIN civicrm_contact contact ON link.entity_id = contact.id
    WHERE link.civishare_id = %1
      AND link.civishare_node_id = %2", [
          1 => [$remote_contact_id, 'String'],
          2 => [$this->remote_node->getID(), 'Integer']]);
    if ($peer->fetch()) {
      if ($peer->is_deleted && !CRM_Share_Configuration::processDeletedPeers()) {
        // contact is deleted
        return NULL;
      }

      if ($only_enabled && empty($peer->is_enabled)) {
        // contact is not enabled
        return NULL;
      }

      // all good
      return $peer->contact_id;
    } else {
      return NULL;
    }
  }

  /**
   * Create a new link entry
   *
   * @param $local_contact_id   int the local contact ID
   * @param $remote_contact_id  int the remote (native) contact ID
   * @throws CiviCRM_API3_Exception
   */
  public function createLink($local_contact_id, $remote_contact_id) {
    $record = [
        'entity_id'    => $local_contact_id,
        'entity_table' => 'civicrm_contact'];

    $record_data = [
        'civishare_link.civishare_node'       => $this->remote_node->getID(),
        'civishare_link.civishare_id'         => $this->remote_node->getShareContactID($remote_contact_id),
        'civishare_link.civishare_timestamp'  => date('YmdHis'),
        'civishare_link.civishare_is_enabled' => 1];
    CRM_Share_CustomData::resolveCustomFields($record_data);

    // merge data into record
    foreach ($record_data as $key => $value) {
      $record["{$key}:-1"] = $value;
    }

    civicrm_api3('CustomValue', 'create', $record);
  }

  /**
   * Filter the list of contact IDs to the ones that are currently peered with
   *  this node
   *
   * @param $contact_ids array list of contact_ids
   * @return  array list of contact_ids
   */
  public function getPeeredContactIDS($contact_ids) {
    if (empty($contact_ids)) return [];
    // run a query
    $node_id = $this->remote_node->getID();
    $peered_contact_ids = [];
    $contact_ids_list = implode(',', $contact_ids);
    $query = CRM_Core_DAO::executeQuery("
        SELECT DISTINCT(entity_id)
        FROM civicrm_value_share_link
        WHERE entity_id IN ({$contact_ids_list})
          AND civishare_node_id = {$node_id}");
    while ($query->fetch()) {
      $peered_contact_ids[] = $query->entity_id;
    }
    return $peered_contact_ids;
  }

  /**
   * Get the data "signature" necessary for peering from the given contacts
   *
   * @todo configure this
   *
   * @param $contact_ids array contact IDs
   * @return array data
   */
  public function getPeeringSignatures($contact_ids) {
    // TODO: configurable
    $result = civicrm_api3('Contact', 'get', [
        'id'           => ['IN' => $contact_ids],
        'return'       => 'first_name,last_name,birth_date,id',
        'sequential'   => 0,
        'option.limit' => 0
    ]);
    return $result['values'];
  }
}
