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
 * Provide Metadata for CiviShare.process_changes
 *
 * This is a LOCAL CALL, i.e. it should be triggered e.g. by a cron job
 *
 * This action will cause the system to process all pending changes
 **/
function _civicrm_api3_civi_share_process_changes_spec(&$params) {
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
 * Provide Metadata for CiviShare.process_changes
 *
 * This action will cause the system to process all pending changes
 **/
function civicrm_api3_civi_share_process_changes($params) {
  CRM_Share_Controller::singleton()->log("CiviShare.process_changes request: " . json_encode($params), 'debug');
  $changes_processed = 0;

  while ($changes_processed < $params['limit']) {
    // 1. get next change
    $change = CRM_Share_Change::getNextChangeWithStatus(['PENDING']);
    if (!$change) {
      break;
    }

    // 2. get all other changes connected to the group
    $changes = $change->getAllChangesOfThatGroup(['PENDING']);


    // 3. lock the changes
    $lock = CRM_Share_Controller::singleton()->getChangesLock();
    foreach ($changes as $change) {
      $change->setStatus('BUSY');
    }

    // 4. apply and mark as processed
    foreach ($changes as $change) {
      try {
        CRM_Share_Controller::singleton()->suspendedChangeDetection();
        $change->apply(); // <-- this is where the magic happens
        $change->setStatus('FORWARD');
        CRM_Share_Controller::singleton()->log("Change '{$change->get('change_id')}' applied.", 'debug');
      } catch (Exception $ex) {
        // there was an error
        CRM_Share_Controller::singleton()->log("Error applying change '{$change->get('change_id')}': " . $ex->getMessage(), 'error');
        $change->setStatus('ERROR');
      }
      CRM_Share_Controller::singleton()->resumeChangeDetection();
    }

    // release lock
    CRM_Share_Controller::singleton()->releaseLock($lock);
  }

  return civicrm_api3_create_success();
}
