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

require_once 'share.civix.php';
use CRM_Share_ExtensionUtil as E;


/**
 * Add an action for creating donation receipts after doing a search
 *
 * @param string $objectType specifies the component
 * @param array $tasks the list of actions
 *
 * @access public
 */
function share_civicrm_searchTasks($objectType, &$tasks) {
  // add PEER task to contribution list
  if ($objectType == 'contact') {
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $tasks[] = array(
          'title'  => E::ts('Peer Contacts (CiviShare)'),
          'class'  => 'CRM_Share_Form_Task_PeerTask',
          'result' => false);
    }
  }
}

/**
 * Pre hook: use for change detection
 */
function share_civicrm_pre($op, $objectName, $id, &$params) {
  if ($op == 'create' || $op == 'edit' || $op == 'delete') {
    if (CRM_Share_Configuration::hook_change_detection_enabled()) {
      CRM_Share_ChangeDetectionByHook::processPre($op, $objectName, $id, $params);
    }
  }
}


/**
 * Post hook: use for change detection
 */
function share_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  if ($op == 'create' || $op == 'edit' || $op == 'delete') {
    if (CRM_Share_Configuration::hook_change_detection_enabled()) {
      CRM_Share_ChangeDetectionByHook::processPost($op, $objectName, $objectId, $objectRef);
    }
  }
}



/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function share_civicrm_config(&$config) {
  _share_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function share_civicrm_xmlMenu(&$files) {
  _share_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function share_civicrm_install() {
  _share_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function share_civicrm_postInstall() {
  _share_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function share_civicrm_uninstall() {
  _share_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function share_civicrm_enable() {
  _share_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function share_civicrm_disable() {
  _share_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function share_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _share_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function share_civicrm_managed(&$entities) {
  _share_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function share_civicrm_caseTypes(&$caseTypes) {
  _share_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function share_civicrm_angularModules(&$angularModules) {
  _share_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function share_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _share_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function share_civicrm_entityTypes(&$entityTypes) {
  _share_civix_civicrm_entityTypes($entityTypes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function share_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function share_civicrm_navigationMenu(&$menu) {
  _share_civix_insert_navigation_menu($menu, 'Mailings', array(
    'label' => E::ts('New subliminal message'),
    'name' => 'mailing_subliminal_message',
    'url' => 'civicrm/mailing/subliminal',
    'permission' => 'access CiviMail',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _share_civix_navigationMenu($menu);
} // */
