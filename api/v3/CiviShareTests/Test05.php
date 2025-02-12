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

use Civi\Share\Message;

/**
 * Test05 for CiviShare - test membership processor
 *
 * - create two contacts, peer them,
 * - set up membership for one
 * - create change record
 * - send to the other (local) node
 * - check if membership was created
 *
 * @todo migrate to unit tests (once running)
 */
function civicrm_api3_civi_share_tests_test05(&$params) {
  // allow local loops
  define('CIVISHARE_ALLOW_LOCAL_LOOP', 1);

  if (!function_exists('xcm_civicrm_config')) {
    throw new \Exception('This test requires XCM');
  }

  //  TEST SETUP
  // create a local node
  CRM_Share_TestTools::clearCiviShareConfig();

  // create and pair two contacts
  // @todo migrate peering to service
  $peering = new \Civi\Share\IdentityTrackerContactPeering();
  $local_contact = \Civi\Api4\Contact::create(TRUE)
    ->addValue('first_name', base64_encode(random_bytes(8)))
    ->addValue('last_name', base64_encode(random_bytes(8)))
    ->addValue('contact_type', 'Individual')
    ->execute()
    ->first();

  $membership_type = \Civi\Api4\MembershipType::get(TRUE)
    ->addSelect('id')
    ->setLimit(1)
    ->execute()
    ->first();

  $remote_contact = \Civi\Api4\Contact::create(TRUE)
    ->addValue('first_name', base64_encode(random_bytes(8)))
    ->addValue('last_name', base64_encode(random_bytes(8)))
    ->addValue('contact_id', $local_contact['id'])
    ->addValue('contact_type', 'Individual')
    ->execute()
    ->first();

  // generate a local node
  $local_node = \Civi\Api4\ShareNode::create(FALSE)
    ->addValue('name', 'Local Node 1')
    ->addValue('short_name', 'LOCAL1')
    ->addValue('is_local', TRUE)
    ->addValue('description', 'automated test node')
    ->addValue('is_enabled', TRUE)
    ->addValue('receive_identifiers', CRM_Utils_Array::implodePadded([]))
    ->addValue('send_identifiers', CRM_Utils_Array::implodePadded([]))
    ->execute()
    ->first();

  // create a "remote" node
  $remote_node = \Civi\Api4\ShareNode::create(FALSE)
    ->addValue('name', 'test_04_local')
    ->addValue('short_name', 'FAKE-REMOTE')
    ->addValue('is_local', FALSE)
    ->addValue('is_enabled', TRUE)
    ->execute()
    ->first();

  // create a node peering (cheekily on two local nodes)
  $shared_key = base64_encode(random_bytes(32));
  $node_peering = \Civi\Api4\ShareNodePeering::create(TRUE)
    ->addValue('local_node', $local_node['id'])
    ->addValue('remote_node', $remote_node['id'])
    ->addValue('is_enabled', TRUE)
    ->addValue('shared_secret', $shared_key)
    ->execute();

  // create a contact peering
  $peering->peer($remote_contact['id'], $local_contact['id'], $remote_node['id'], $local_node['id']);


  // TEST RUN: create a new membership

  // create a membership
  $membershipType = \Civi\Api4\MembershipType::get(TRUE)
    ->addSelect('id')
    ->addWhere('is_active', '=', TRUE)
    ->setLimit(1)->execute()->first();
  $membership = \Civi\Api4\Membership::create(TRUE)
    ->addValue('contact_id', $local_contact['id'])
    ->addValue('membership_type_id', $membershipType['id'])
    ->execute()
    ->first();

  //  TEST SETUP

  $data_after = [
    'first_name' => base64_encode(random_bytes(8)),
    'last_name'  => base64_encode(random_bytes(8)),
    'contact_type' => 'Individual',
  // fake contact ID
    'local_contact_id' => 10000 + rand(10000, 99999999),
    'membership_type_id' => $membershipType['id'],
  ];

  // insert a share handler
  \Civi\Api4\ShareHandler::create(TRUE)
    ->addValue('name', 'Default')
    ->addValue('class', 'Civi\Share\ChangeProcessor\SimpleMembershipChangeProcessor')
    ->addValue('weight', 100)
    ->addValue('is_enabled', TRUE)
    ->execute();

  // register a 'civishare.change.membership.base' change
  $change = \Civi\Api4\ShareChange::create(TRUE)
    ->addValue('change_id', 'TEST-' . $virtual_contact_after['last_name'])
    ->addValue('change_group_id', NULL)
    ->addValue('status', \Civi\Share\Change::STATUS_PENDING)
    ->addValue('change_type', 'civishare.change.membership.base')
    ->addValue('data_before', '')
    ->addValue('data_after', $data_after)
    ->addValue('local_contact_id', $data_after['local_contact_id'])
    ->addValue('contact_id', $data_after['local_contact_id'])
    ->addValue('source_node_id', $remote_node['id'])
    ->addValue('change_date', date('Y-m-d H:i:s'))
    ->addValue('received_date', date('Y-m-d H:i:s'))
  // since this is a local change
    ->addValue('received_date', date('Y-m-d H:i:s'))
    // no processed_date yet
  // not triggered by applying another change
    ->addValue('triggerd_by', '')
    ->execute();
  $change_id = $change->first()['id'];

  // create and send a change message
  $change_message = new Message();
  $change_message->addChangeById($change_id);
  $change_message->processChanges($local_node['id']);
  $change_message->setShareApi(Civi::service('civi.share.mock.api'));
  $change_message->sendToAll();

  // this should now be processed
  if (!$change_message->allChangesProcessed()) {
    // todo: replace with ->assertTrue()
    throw new Exception('Changes were NOT processed.');
  }

  // see if the membership has been altered
  $membership_after = \civicrm_api3('Membership', 'getsingle', ['id' => $membership['id']]);
  if ($membership['membership_type_id'] != $membership_after['membership_type_id']) {
    throw new Exception('Change data not applied!');
  }
  return civicrm_api3_create_success();
}
