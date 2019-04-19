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
require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Share_Form_Task_PeerTask extends CRM_Contact_Form_Task {


  function preProcess() {
    parent::preProcess();
    CRM_Utils_System::setTitle(E::ts('Peer contacts with another CiviShare node'));
  }


  function buildQuickForm() {
    $contact_ids = implode(',', $this->_contactIds);

    $this->add('select',
        'node_id',
        E::ts('Peer Node'),
        CRM_Share_Node::getNodeList(),
        true,
        array('class' => 'crm-select2'));

    CRM_Core_Form::addDefaultButtons(E::ts('Peer'));

    parent::buildQuickForm();
  }

  function postProcess() {
    $values = $this->exportValues();
    $remote_host = CRM_Share_Node::getNodeByID($values['node_id']);
    $peering = new CRM_Share_Peering($remote_host);
    $result = $peering->activePeer($this->_contactIds);

    // TODO:
    CRM_Core_Session::setStatus(E::ts("TODO:"), E::ts("Peering Completed"), 'info');
  }
}
