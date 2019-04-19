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
 * Provide Metadata for CiviShare.peer
 *
 * This action allows you to propose contacts for peering to another node
 **/
function _civicrm_api3_civi_share_peer_spec(&$params) {
  $params['record']     = array(
      'name'         => 'records',
      'api.required' => 1,
      'type'         => CRM_Utils_Type::T_LONGTEXT,
      'title'        => 'JSON encoded contact records. Array [contact_id => ["first_name" => "Karl", ...]]',
  );
  $params['sender_key'] = array(
      'name'         => 'sender_key',
      'api.required' => 1,
      'type'         => CRM_Utils_Type::T_STRING,
      'title'        => 'Key identifying the sender'
  );
}

/**
 * Provide Metadata for CiviShare.peer
 *
 * This action allows you to propose contacts for peering to another node
 **/
function civicrm_api3_civi_share_peer($params) {
  $peering_results = [];

  $remote_node = CRM_Share_Node::getNode($params['sender_key']);
  if (empty($remote_node)) {
    return civicrm_api3_create_error("Key not accepted. Maybe the nodes aren't peered yet?");
  }

  // get records
  $records = $params['records'];
  if (is_string($records)) {
    $records = json_decode($records, TRUE);
  }

  $peering = new CRM_Share_Peering($remote_node);
  foreach ($records as $contact_id => $contact_data) {
    $peering_results[$contact_id] = $peering->peer($contact_id, $contact_data);
  }

  return civicrm_api3_create_success($peering_results);
}
