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

declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects
require_once 'share.civix.php';
// phpcs:enable

use CRM_Share_ExtensionUtil as E;
use Symfony\Component\DependencyInjection\ContainerBuilder;

function _share_composer_autoload(): void {
  if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
  }
}

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
      $tasks[] = [
        'title'  => E::ts('Peer Contacts (CiviShare)'),
        'class'  => 'CRM_Share_Form_Task_PeerTask',
        'result' => FALSE,
      ];
    }
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function share_civicrm_config(&$config) {
  _share_composer_autoload();
  _share_civix_civicrm_config($config);
  $defaultContactBaseChangeProcessor = new \Civi\Share\ChangeProcessor\DefaultContactBaseChangeProcessor();
  $defaultContactBaseChangeProcessor
    ->register([\Civi\Share\ChangeProcessor\AbstractChangeProcessor::CHANGE_TYPE_CONTACT_BASE]);
}

/**
 * Implements hook_civicrm_container()
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_container/
 */
function share_civicrm_container(ContainerBuilder $container): void {
  _share_composer_autoload();
}

/**
 * Implements hook_civicrm_container()
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_container/
 */
function share_civicrm_container(ContainerBuilder $container) {
  _share_composer_autoload();
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
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function share_civicrm_enable() {
  _share_civix_civicrm_enable();
}

function share_civicrm_permission(array &$permissions): void {
  $permissions += \Civi\Share\Permissions::getPermissions();
}
