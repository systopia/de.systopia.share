<?php

function civicrm_api3_civi_share_tests_test_civi_m_r_f(&$params) {
  // Create a local node.
  $local_node = \Civi\Api4\ShareNode::create(FALSE)
    ->addValue('name', 'Local Node 1')
    ->addValue('short_name', 'test_01_local')
    ->addValue('is_local', TRUE)
    ->addValue('description', "automated test node")
    ->addValue('is_enabled', TRUE)
    ->addValue('receive_identifiers', CRM_Utils_Array::implodePadded([]))
    ->addValue('send_identifiers', CRM_Utils_Array::implodePadded([]))
    ->execute()
    ->single();

  // Create a remote node.
  $remote_node = \Civi\Api4\ShareNode::create(FALSE)
    ->addValue('name', 'test_02_local')
    ->addValue('short_name', 'Remote Node')
    ->addValue('is_local', FALSE)
    ->addValue('is_enabled', TRUE)
    ->addValue('rest_url', 'TODO')
    ->addValue('api_key', 'TODO')
    ->addValue('auth_key', 'TODO')
    ->execute()
    ->single();

  // Create a peering.
  $shared_key = base64_encode(random_bytes(64));
  $node_peering = \Civi\Api4\ShareNodePeering::create(TRUE)
    ->addValue('local_node', $local_node['id'])
    ->addValue('remote_node', $remote_node['id'])
    ->addValue('is_enabled', TRUE)
    ->addValue('shared_secret', $shared_key)
    ->execute()
    ->single();

  // Send a message using the peering.
  $shareApi = Civi::service('civi.share.api');
  $shareApi->sendMessage($node_peering['id'], '');
}
