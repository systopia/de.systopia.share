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

declare(strict_types = 1);

use Civi\Share\Message;
use Civi\Share\ChangeProcessingEvent;

/**
 * Test01 for CiviShare
 *
 * - Set up two local nodes
 * - peer them
 * - send data
 * - validate
 *
 * @todo migrate to unit tests (once running)
 */
function civicrm_api3_civi_share_tests_test01(&$params) {
  // allow local loops
  define('CIVISHARE_ALLOW_LOCAL_LOOP', 1);

  // create a local node
  CRM_Share_TestTools::clearCiviShareConfig();

  // generate a local node
  $local_node = \Civi\Api4\ShareNode::create(FALSE)
    ->addValue('name', 'Local Node 1')
    ->addValue('short_name', 'test_01_local')
    ->addValue('is_local', TRUE)
    ->addValue('description', 'automated test node')
  //    ->addValue('rest_url', 'TODO')
  //    ->addValue('api_key', 'TODO')
  //    ->addValue('auth_key', 'TODO')
    ->addValue('is_enabled', TRUE)
    ->addValue('receive_identifiers', CRM_Utils_Array::implodePadded([]))
    ->addValue('send_identifiers', CRM_Utils_Array::implodePadded([]))
    ->execute()
    ->first();

  // create a "remote" node
  $remote_node = \Civi\Api4\ShareNode::create(FALSE)
    ->addValue('name', 'test_03_local')
    ->addValue('short_name', 'Fake "Remote" Node')
    ->addValue('is_local', FALSE)
    ->addValue('is_enabled', TRUE)
    ->execute()
    ->first();

  // create a peering (cheekily on two local nodes)
  $shared_key = base64_encode(random_bytes(32));
  $node_peering = \Civi\Api4\ShareNodePeering::create(TRUE)
    ->addValue('local_node', $local_node['id'])
    ->addValue('remote_node', $remote_node['id'])
    ->addValue('is_enabled', TRUE)
    ->addValue('shared_secret', $shared_key)
    ->execute();

  // create test change
  $change = \Civi\Api4\ShareChange::create(TRUE)
    ->addValue('change_id', 'TODO')
    ->addValue('change_group_id', NULL)
    ->addValue('status', \Civi\Share\Change::STATUS_LOCAL)
    ->addValue('change_type', 'civishare.change.test')
    ->addValue('status', 'PENDING')
    ->addValue('local_contact_id', \CRM_Core_Session::getLoggedInContactID())
    ->addValue('source_node_id', $local_node['id'])
    ->addValue('change_date', date('Y-m-d H:i:s'))
  // since this is a local change
    ->addValue('received_date', date('Y-m-d H:i:s'))
    // no processed_date yet
  // not triggered by applying another change
    ->addValue('triggerd_by', '')
  // empty as this is a test
    ->addValue('data_before', '')
  // empty as this is a test
    ->addValue('data_after', '')
    ->execute();
  $change_id = $change->first()['id'];

  // add a dummy listener to the 'civishare.change.test' change type
  $result = \Civi::dispatcher()->addListener(
    ChangeprocessingEvent::NAME,
    'civicrm_civi_share_test_register_test_hander',
    ChangeProcessingEvent::LATE_PROCESSING
  );

  // create a change message
  $change_message = new Message();
  $change_message->addChangeById($change_id);
  $change_message->processChanges($local_node['id']);

  // send
  $change_message->setShareApi(Civi::service('civi.share.mock.api'));
  $change_message->sendToAll();

  // this should now be processed
  if (!$change_message->allChangesProcessed()) {
    // todo: replace with ->assertTrue()
    throw new Exception('Changes were NOT processed.');
  }

  return civicrm_api3_create_success();
}

/**
 * Process test events
 *
 * @param \Civi\Share\ChangeProcessingEvent $processing_event
 * @param string $event_type
 * @param $dispatcher
 * @return void
 */
function civicrm_civi_share_test_register_test_hander($processing_event, $event_type, $dispatcher) {
  // nothing to do here
  if ($processing_event->isProcessed()) {
    return;
  }

  // check if this is the one we're looking for
  if ($processing_event->hasChangeType('civishare.change.test')) {
    $processing_event->setProcessed();
  }
}
