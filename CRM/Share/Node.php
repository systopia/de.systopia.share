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
 * Represents a CiviShare node (most likely a remote one)
 *
 * @deprecated will be handled differently, so this is no longer needed
 */
class CRM_Share_Node {

  protected static $fields = ['name', 'short_name', 'description', 'rest_url', 'site_key', 'api_key', 'is_enabled', 'auth_key', 'receive_profile', 'send_profile'];

  // node cache
  protected static $_node_by_id = [];

  protected $node_id;
  protected $node_data;


  protected function __construct($node_id) {
    $this->node_id = (int) $node_id;

    // load node data
    $node = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_share_node WHERE id = {$this->node_id};");
    if ($node->fetch()) {
      $this->node_data = [];
      foreach (self::$fields as $field) {
        $this->node_data[$field] = $node->$field;
      }
    } else {
      throw new Exception("CiviShare node [{$node_id}] doesn't exist");
    }
  }

  /**
   * Get node by ID
   *
   * @param $node_id int node ID
   *
   * @return CRM_Share_Node|null
   */
  public static function getNodeByID($node_id) {
    try {
      if (!isset(self::$_node_by_id[$node_id])) {
        self::$_node_by_id[$node_id] = new CRM_Share_Node($node_id);
      }
      return self::$_node_by_id[$node_id];
    } catch(Exception $ex) {
      return NULL;
    }
  }

  /**
   * Get node by key
   *
   * @param $shared_key string the key this host was authorised with
   *
   * @return CRM_Share_Node|null
   */
  public static function getNode($shared_key) {
    $node_id = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_share_node WHERE auth_key = %1;", [1 => [$shared_key, 'String']]);
    if ($node_id) {
      return self::getNodeByID($node_id);
    } else {
      return NULL;
    }
  }

  /**
   * Get a list of nodes connected to the given contact
   *
   * @param $contact_id  int     (local) contact ID
   * @param $active_only boolean should include only active connections?
   * @return array list of CRM_Share_Node objects
   */
  public static function getNodesForContact($contact_id, $active_only = TRUE) {
    $node_list = [];
    $contact_id = (int) $contact_id;

    // should we find only active ones?
    if ($active_only) {
      $AND_ENABLED_CLAUSE = 'AND is_enabled = 1';
    } else {
      $AND_ENABLED_CLAUSE = '';
    }

    $node = CRM_Core_DAO::executeQuery("
        SELECT civishare_node_id AS nid
        FROM civicrm_value_share_link
        WHERE entity_id = {$contact_id}
        {$AND_ENABLED_CLAUSE}");
    while ($node->fetch()) {
      $node_list[$node->nid] = self::getNodeByID($node->nid);
    }
    return $node_list;
  }


  /**
   * Get a list of node_id => node name
   */
  public static function getNodeList() {
    $node_list = [];
    $node = CRM_Core_DAO::executeQuery("
        SELECT id AS nid, name, short_name
        FROM civicrm_share_node
        WHERE is_enabled = 1");
    while ($node->fetch()) {
      if ($node->short_name != 'LOCAL') {
        $node_list[$node->nid] = "[{$node->short_name}] {$node->name}";
      }
    }
    return $node_list;
  }

  /**
   * Get the node's bi-directional peering key
   * @return string key
   */
  public function getKey() {
    return $this->node_data['auth_key'];
  }

  /**
   * Get the node's bi-directional peering key
   * @return string key
   */
  public function getID() {
    return $this->node_id;
  }

  public function getShortName() {
    return $this->node_data['short_name'];
  }

  /**
   * Issues API3 call remotely to that node
   * @param $entity string API entity
   * @param $action string API action
   * @param $params array parameters
   * @return array result if successful
   * @throws CiviCRM_API3_Exception if there is an error
   */
  public function api3($entity, $action, $params) {
    $result = CRM_Share_Controller::singleton()->restAPI3(
        $entity,
        $action,
        $params,
        $this->node_data['rest_url'],
        $this->node_data['site_key'],
        $this->node_data['api_key']);
    if (empty($result['is_error'])) {
      return $result;
    } else {
      throw new CiviCRM_API3_Exception($result['error_message'], '0723');
    }
  }

  /**
   * Get the unified (CiviShare) contact ID for a contact from this node
   */
  public function getShareContactID($native_contact_id) {
    return "{$this->node_data['short_name']}-{$native_contact_id}";
  }
}
