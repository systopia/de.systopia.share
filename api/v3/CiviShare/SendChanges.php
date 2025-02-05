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
 * Provide Metadata for CiviShare.send_changes
 *
 * This is a LOCAL CALL, i.e. it should be triggered e.g. by a cron job
 *
 * This action will cause the system to propagate pending changes in the local
 *   database to all connected nodes
 **/
function _civicrm_api3_civi_share_send_changes_spec(&$params) {
  $params['limit'] = array(
      'name'         => 'limit',
      'api.default'  => 20,
      'type'         => CRM_Utils_Type::T_INT,
      'title'        => 'Limit the amount of changes sent to this number.',
  );
  $params['change_ids'] = array(
      'name'         => 'change_ids',
      'api.required' => 0,
      'type'         => CRM_Utils_Type::T_STRING,
      'title'        => 'Change ID(s)',
      'description'  => 'comma-separated list of change IDs to be processed',
  );
}

/**
 * Provide Metadata for CiviShare.send_changes
 *
 * This action will cause the system to propagate pending changes in the local
 *   database to all connected nodes
 **/
function civicrm_api3_civi_share_send_changes($params) {
  //CRM_Share_Controller::singleton()->log("CiviShare.send_changes request: " . json_encode($params), 'debug');
  $changes_processed = 0;

  // TODO: send only one request per node

  while ($changes_processed < $params['limit']) {
    $lock = CRM_Share_Controller::singleton()->getChangesLock();

    // 1. get next change
    $change = CRM_Share_Change::getNextChangeWithStatus(['LOCAL', 'FORWARD']);
    if (!$change) {
      // no more changes
      break;
    }

    // 2. get all other changes connected to the group
    $changes = $change->getAllChangesOfThatGroup(['LOCAL', 'FORWARD']);

    // 3. send those to all connected nodes
    $serialised_changes = [];
    foreach ($changes as $change) {
      $serialised_changes[] = $change->toArray();
    }

    $nodes = CRM_Share_Node::getNodesForContact($change->getContactID());
    $error = FALSE;
    foreach ($nodes as $node) {  /** @var CRM_Share_Node $node */
      try {
        $node->api3('CiviShare', 'store_changes', [
            'changes'    => json_encode($serialised_changes),
            'sender_key' => $node->getKey(),
        ]);
        CRM_Share_Controller::singleton()->log("Change '{$change->get('change_id')}' sent to {$node->getShortName()}", 'debug');
      } catch (Exception $ex) {
        $error = $ex->getMessage();
        CRM_Share_Controller::singleton()->log("ERROR while sending '{$change->get('change_id')}' to {$node->getShortName()}: {$error}" , 'error');
      }
    }

    // 4. mark as sent
    foreach ($changes as $change) {
      if ($error) {
        $change->setStatus('ERROR');
      } else {
        $change->setStatus('DONE');
      }
    }

    CRM_Share_Controller::singleton()->releaseLock($lock);
  }

  return civicrm_api3_create_success();
}
