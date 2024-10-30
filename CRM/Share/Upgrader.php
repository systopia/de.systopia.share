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

use CRM_Share_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Share_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * Installation
   */
  public function install() {
    // generate the data structures
    require_once 'CRM/Share/CustomData.php';
    $customData = new CRM_Share_CustomData('de.systopia.share');
    $customData->syncCustomGroup(__DIR__ . '/../../resources/custom_group_share_link.json');
  }

  /**
   * Example: Work with entities usually not available during the install step.
   *
   * This method can be used for any post-install tasks. For example, if a step
   * of your installation depends on accessing an entity that is itself
   * created during the installation (e.g., a setting or a managed entity), do
   * so here to avoid order of operation problems.
   *
  public function postInstall() {
    $customFieldId = civicrm_api3('CustomField', 'getvalue', array(
      'return' => array("id"),
      'name' => "customFieldCreatedViaManagedHook",
    ));
    civicrm_api3('Setting', 'create', array(
      'myWeirdFieldSetting' => array('id' => $customFieldId, 'weirdness' => 1),
    ));
  }


  /**
   * Extension gets enabled
   *
  public function enable() {
  }

  /**
   * Example: Run a simple query when a module is disabled.
   *
  public function disable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  }

  /**
   * debugging upgrader
   */
  public function upgrade_0002() {
    $this->ctx->log->info('DEV upgrade');

    // update the data structures
    require_once 'CRM/Share/CustomData.php';
    $customData = new CRM_Share_CustomData('de.systopia.share');
    $customData->syncCustomGroup(__DIR__ . '/../../resources/custom_group_share_link.json');
    return TRUE;
  }


  /**
   * Example: Run an external SQL script.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4201() {
    $this->ctx->log->info('Applying update 4201');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4201.sql');
    return TRUE;
  } // */

}
