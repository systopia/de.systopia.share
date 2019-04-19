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

    // 2. get all other changes connected to the group

    $lock = CRM_Share_Controller::singleton()->getChangesLock();

    // 3. process those changes

    // 4. mark as processed

    CRM_Share_Controller::singleton()->releaseLock($lock);
  }

  return civicrm_api3_create_success();
}
