<?php
/*-------------------------------------------------------+
| CiviShare                                              |
| Copyright (C) 2024 SYSTOPIA                            |
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

use \Civi\Share\Message;


/**
 * Test02 for CiviShare - test the contact peering
 *
 * - Set up two local nodes
 * - peer them
 * - create two contacts and peer them
 * - validate
 *
 * @todo migrate to unit tests (once running)
 **/
function civicrm_api3_civi_share_tests_test02(&$params) {
  define('CIVISHARE_ALLOW_LOCAL_LOOP', 1); // allow local loops

  // create a local node
  CRM_Share_TestTools::clearCiviShareConfig();

  // generate a local node
  $local_node = \Civi\Api4\ShareNode::create(false)
    ->addValue('name', 'Local Node 1')
    ->addValue('short_name', 'LOCAL1')
    ->addValue('is_local', true)
    ->addValue('description', "automated test node")
    ->addValue('is_enabled', true)
    ->addValue('receive_identifiers', CRM_Utils_Array::implodePadded([]))
    ->addValue('send_identifiers', CRM_Utils_Array::implodePadded([]))
    ->execute()
    ->first();

  // create a "remote" node
  $remote_node = \Civi\Api4\ShareNode::create(false)
    ->addValue('name', 'test_03_local')
    ->addValue('short_name', 'FAKE-REMOTE')
    ->addValue('is_local', false)
    ->addValue('is_enabled', true)
    ->execute()
    ->first();

  // create a peering (cheekily on two local nodes)
  $shared_key = base64_encode(random_bytes(64));
  $node_peering = \Civi\Api4\ShareNodePeering::create(TRUE)
    ->addValue('local_node', $local_node['id'])
    ->addValue('remote_node', $remote_node['id'])
    ->addValue('is_enabled', true)
    ->addValue('shared_secret', $shared_key)
    ->execute();


  // create and pair two contacts
  // @todo migrate peering to service
  $peering = new \Civi\Share\IdentityTrackerContactPeering();
  $local_contact = \Civi\Api4\Contact::create(TRUE)
    ->addValue('first_name', base64_encode(random_bytes(8)))
    ->addValue('last_name', base64_encode(random_bytes(8)))
    ->addValue('contact_type', 'Individual')
    ->execute()
    ->first();

  $remote_contact = \Civi\Api4\Contact::create(TRUE)
    ->addValue('first_name', base64_encode(random_bytes(8)))
    ->addValue('last_name', base64_encode(random_bytes(8)))
    ->addValue('contact_type', 'Individual')
    ->execute()
    ->first();

  // create a peering
  $peering->peer($remote_contact['id'], $local_contact['id'], $remote_node['id'], $local_node['id']);

  // create a change data set
  $change_data_before = [
    'contact_id' => $remote_contact['id'],
    'first_name' => $remote_contact['first_name'],
    'last_name' => $remote_contact['last_name'],
  ];
  $change_data_after = [
    'contact_id' => $remote_contact['id'],
    'first_name' => base64_encode(random_bytes(8)),
    'last_name' => base64_encode(random_bytes(8)),
  ];

  // register a 'civishare.change.contact.base' change
  $change = \Civi\Api4\ShareChange::create(TRUE)
    ->addValue('change_id', 'TODO')
    ->addValue('change_group_id', null)
    ->addValue('status', \Civi\Api4\ShareChange::STATUS_PENDING)
    ->addValue('change_type', 'civishare.change.contact.base')
    ->addValue('status', 'PENDING')
    ->addValue('data_before', json_encode($change_data_before, true))
    ->addValue('data_after', json_encode($change_data_after, true))
    ->addValue('local_contact_id', $remote_contact['id'])
    ->addValue('source_node_id', $remote_node['id'])
    ->addValue('change_date', date('Y-m-d H:i:s'))
    ->addValue('received_date', date('Y-m-d H:i:s')) // since this is a local change
    // no processed_date yet
    ->addValue('triggerd_by', '') // not triggered by applying another change
    ->execute();
  $change_id = $change->first()['id'];

  // register a 'civishare.change.contact.base' change processor
  $result = \Civi::dispatcher()->addListener(
    'de.systopia.change.process',
    'civicrm_civi_share_test_register_test2_hander'
  );

  // create a change message
  $change_message = new Message();
  $change_message->addChangeById($change_id);
  $change_message->processChanges($local_node['id']);

  // send
  $change_message->send();

  // this should now be processed
  if (!$change_message->allChangesProcessed()) {
    // todo: replace with ->assertTrue()
    throw new Exception("Changes were NOT processed.");
  }

  return civicrm_api3_create_success();
}


/**
 * Process test data: an update for contact data
 *
 * @param \Civi\Share\ChangeProcessingEvent $processing_event
 * @param string $event_type
 * @param $dispatcher
 * @return void
 */
function civicrm_civi_share_test_register_test2_hander($processing_event, $event_type, $dispatcher)
{
  // nothing to do here
  if ($processing_event->isProcessed()) return;

  // check if this is the one we're looking for
  if ($processing_event->hasChangeType('civishare.change.contact.base')) {
    // do the processing!
    $data_before = $processing_event->getChangeDataBefore();
    $data_after = $processing_event->getChangeDataAfter();
    $remote_contact_id = $processing_event->getContactID();
    $change_date = $processing_event->getChangeDataBefore();

    // use peering service to find local_contact_id
    // @todo migrate peering to service
    $peering = new \Civi\Share\IdentityTrackerContactPeering();
    $change = $processing_event->getChange();
    $change_data = $processing_event->getChangeDataAfter();
    $local_contact_id = $peering->getLocalContactId($remote_contact_id, $change['source_node_id'], $change['local_node_id']);
    $processing_event->setProcessed();
  }
}
