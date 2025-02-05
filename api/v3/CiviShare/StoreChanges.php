<?php
/*-------------------------------------------------------+
| CiviShare                                              |
| Copyright (C) 2025 SYSTOPIA                            |
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
 * Provide Metadata for CiviShare.store_changes
 *
 * This is a REMOTE CALL, i.e. it will be triggered by a connected node
 *
 * This action will cause the system to store the changes submitted.
 *  Remark: the changes will merely be stored, not processed - this will be done by CiviShare.store_changes
 **/
function _civicrm_api3_civi_share_store_changes_spec(&$params) {
  $params['changes'] = array(
      'name'         => 'changes',
      'api.required' => 1,
      'type'         => CRM_Utils_Type::T_LONGTEXT,
      'title'        => 'Changes to store',
      'description'  => 'JSON encoded contact records. Array of changes',
  );
  $params['sender_key'] = array(
      'name'         => 'sender_key',
      'api.required' => 1,
      'type'         => CRM_Utils_Type::T_STRING,
      'title'        => 'Key identifying the sender',
      'description'  => 'This key is a shared secret between the calling node and this one',
  );
}

/**
 * CiviShare.store_changes will cause the system to store the changes submitted.
 *  Remark: the changes will merely be stored, not processed - this will be done by CiviShare.store_changes
**/
function civicrm_api3_civi_share_store_changes($params) {
  //CRM_Share_Controller::singleton()->log("CiviShare.store_changes request: " . json_encode($params), 'debug');

  // get remote node
  $remote_node = CRM_Share_Node::getNode($params['sender_key']);
  if (empty($remote_node)) {
    return civicrm_api3_create_error("Key not accepted. Maybe the nodes aren't peered yet?");
  }

  // TODO: do we need the lock here?
  // $lock = CRM_Share_Controller::singleton()->getChangesLock();

  $error_count = 0;
  $changes = json_decode($params['changes'], TRUE);
  foreach ($changes as $change) {
    // store change
    try {
      if (!CRM_Share_Change::storeChange($remote_node, $change)) {
        $error_count += 1;
      }
    } catch(Exception $ex) {
      $error_count += 1;
    }
  }

  // TODO: do we need the lock here?
  // CRM_Share_Controller::singleton()->releaseLock($lock);

  if ($error_count) {
    $count = count($changes);
    return civicrm_api3_create_error("{$error_count} of {$count} changes were rejected. Check the logs.");
  } else {
    return civicrm_api3_create_success();
  }
}
