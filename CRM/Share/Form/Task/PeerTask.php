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

use CRM_Share_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Share_Form_Task_PeerTask extends CRM_Contact_Form_Task {

  public function preProcess() {
    parent::preProcess();
    CRM_Utils_System::setTitle(E::ts('Peer contacts with another CiviShare node'));
  }

  public function buildQuickForm() {
    $contact_ids = implode(',', $this->_contactIds);

    $this->add('select',
        'node_id',
        E::ts('Peer Node'),
        CRM_Share_Node::getNodeList(),
        TRUE,
        ['class' => 'crm-select2']);

    CRM_Core_Form::addDefaultButtons(E::ts('Peer'));

    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();
    $remote_host = CRM_Share_Node::getNodeByID($values['node_id']);
    $peering = new CRM_Share_Peering($remote_host);
    $result = $peering->activePeer($this->_contactIds);

    // Compile result
    $messages = [];
    $messages[] = E::ts(
      '%1 contact(s) were successfully peered with %2',
      [
        1 => $result['NEWLY_PEERED'],
        2 => $remote_host->getShortName(),
      ]
    );
    if (!empty($result['INSUFFICIENT_DATA'])) {
      $messages[] = E::ts(
        '%1 contact(s) did not provide enough data for peering',
        [
          1 => $result['INSUFFICIENT_DATA'],
          2 => $remote_host->getShortName(),
        ]
      );
    }
    if (!empty($result['ALREADY_PEERED'])) {
      $messages[] = E::ts(
        '%1 contact(s) were already peered',
        [
          1 => $result['ALREADY_PEERED'],
          2 => $remote_host->getShortName(),
        ]
      );
    }
    if (!empty($result['NOT_IDENTIFIED'])) {
      $messages[] = E::ts(
        '%1 contact(s) were not found on %2',
        [
          1 => $result['NOT_IDENTIFIED'],
          2 => $remote_host->getShortName(),
        ]
      );
    }
    if (!empty($result['AMBIGUOUS'])) {
      $messages[] = E::ts(
        '%1 contact(s) could not be uniquely identified',
        [
          1 => $result['AMBIGUOUS'],
          2 => $remote_host->getShortName(),
        ]
      );
    }
    if (!empty($result['ERROR'])) {
      $messages[] = E::ts(
        '%1 contact(s) produced an error',
        [
          1 => $result['ERROR'],
          2 => $remote_host->getShortName(),
        ]
      );
    }
    $message = implode(', ', $messages) . '.';
    CRM_Core_Session::setStatus($message, E::ts('Peering Completed'), 'info');
  }

}
