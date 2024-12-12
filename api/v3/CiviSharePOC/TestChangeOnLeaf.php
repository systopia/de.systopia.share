<?php

function _civicrm_api3_civi_share_p_o_c_test_change_on_leaf_spec(&$spec) {
  $spec['clear'] = [
    'title'       => 'Clear CiviShare change entities pending from sending for the "leaf" node',
    'description' => 'Whether to delete all CiviShare change entities local to the "leaf" node which are to be sent out before running this test.',
    'required'    => FALSE,
    'type'        => CRM_Utils_Type::T_BOOLEAN,
  ];
}

/**
 * This test is supposed to be run on the "leaf" node environment.
 * It creates two changes and sends all local changes to all peered remote nodes
 * (when using with the SetupLeaf test only, this should be the "central" node
 * environment only).
 */
function civicrm_api3_civi_share_p_o_c_test_change_on_leaf(&$params) {
  $leafNodeId = \Civi\Api4\ShareNode::get(FALSE)
    ->addSelect('id')
    ->addWhere('short_name', '=', 'leaf')
    ->execute()
    ->single()['id'];

  if ($params['clear'] ?? FALSE) {
    \Civi\Api4\ShareChange::delete(FALSE)
      ->addWhere('source_node_id', '=', $leafNodeId)
      ->addWhere('status', 'IN', \Civi\Share\Change::PENDING_FROM_SENDING_STATUS)
      ->execute();
  }

  // Create changes.
  $change1 = \Civi\Api4\ShareChange::create(FALSE)
    ->addValue('change_type', 'civishare.change.contact.base')
    ->addValue('change_date', '2024-12-09T13:13:29+01:00')
    ->addValue('status', \Civi\Share\Change::STATUS_LOCAL)
    ->addValue('local_contact_id', 312)
    ->addValue('source_node_id', $leafNodeId)
    ->addValue('data_before', [
      'first_name' => 'Karl',
    ])
    ->addValue('data_after', [
      'first_name' => 'Carl',
      'birth_date' => '2000-01-01',
    ])
    ->execute();

  $change2 = \Civi\Api4\ShareChange::create(FALSE)
    ->addValue('change_type', 'civishare.change.contact.base')
    ->addValue('change_date', '2024-12-09T13:13:29+01:00')
    ->addValue('status', \Civi\Share\Change::STATUS_LOCAL)
    ->addValue('local_contact_id', 2312)
    ->addValue('source_node_id', $leafNodeId)
    ->addValue('data_before', [
      'first_name' => 'Karlotta',
    ])
    ->addValue('data_after', [
      'first_name' => 'Escarlata',
      'last_name' => 'La Pirata',
    ])
    ->execute();

  // Send changes.
  $apiResult = \Civi\Api4\ShareChangeMessage::send()
    ->setSourceNodeId($leafNodeId)
    ->execute();

  $result = [
    'changes' => [$change1, $change2],
    'result' => $apiResult,
  ];

  return civicrm_api3_create_success($result);
}
