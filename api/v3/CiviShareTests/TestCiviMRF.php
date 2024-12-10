<?php

function civicrm_api3_civi_share_tests_test_civi_m_r_f(&$params) {
//  // Delete existing peerings and nodes.
//  \Civi\Api4\ShareNodePeering::delete(TRUE)
//    ->addWhere('id', 'IS NOT NULL')
//    ->execute();
//  \Civi\Api4\ShareNode::delete(TRUE)
//    ->addWhere('id', 'IS NOT NULL')
//    ->execute();
//  \Civi\Api4\ShareChange::delete(TRUE)
//    ->addWhere('id', 'IS NOT NULL')
//    ->execute();

  // Create a local node.
  $local_node = \Civi\Api4\ShareNode::create(FALSE)
    ->addValue('name', 'local_node_01')
    ->addValue('short_name', 'local_node_01')
    ->addValue('is_local', TRUE)
    ->addValue('description', "automated test node")
    ->addValue('is_enabled', TRUE)
    ->addValue('receive_identifiers', CRM_Utils_Array::implodePadded([]))
    ->addValue('send_identifiers', CRM_Utils_Array::implodePadded([]))
    ->execute()
    ->single();

  // Create a remote node.
  $remote_node = \Civi\Api4\ShareNode::create(FALSE)
    ->addValue('name', 'remote_node_01')
    ->addValue('short_name', 'remote_node_01')
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

//  $testMessage = [
//    'id' => 'some-unique-id',
//    'payload_signature' => '/HbL/n2GaZWex15bmNquRUYWUriGEPpncqcIUQkqgwoltCzQU+x2IjMZZNgSFJ2oMJBk24AzHn/WZw8eOn5RPX2frgjtPtR1FO24H7YqD8X59rZMBHgRN+4TYl+hJjo8pEpgTQvp0WMmV8DZNEVZBjmwdmtlJ4e/f5SNWRi2kNQ=',
//    'payload' => [
//      'sender' => 'local_node_01',
//      'sent' => '2024-12-09T13:13:29+01:00',
//      'changes' => [
//        [
//          'type' => 'civishare.change.contact.base',
//          'timestamp' => '2024-12-09T13:13:29+01:00',
//          'entity' => 'Contact',
//          'entity_reference' => '312',
//          'local_contact_id' => '312',
//          'attribute_changes' => [
//            [
//              'name' => 'first_name',
//              'from' => 'Karl',
//              'to' => 'Carl',
//            ],
//            [
//              'name' => 'birth_date',
//              'from' => '',
//              'to' => '2000-01-01',
//            ],
//          ],
//          'loop_detection' => [
//            '5R+hJjo8pEpgTQvp0WMmV8DZNEVZB',
//          ],
//        ],
//        [
//          'type' => 'civishare.change.contact.base',
//          'timestamp' => '2024-12-09T13:13:29+01:00',
//          'entity' => 'Contact',
//          'entity_reference' => '2312',
//          'local_contact_id' => '2312',
//          'attribute_changes' => [
//            [
//              'name' => 'first_name',
//              'from' => 'Karlotta',
//              'to' => 'Escarlata',
//            ],
//            [
//              'name' => 'last_name',
//              'from' => '',
//              'to' => 'La Pirata',
//            ],
//          ],
//          'loop_detection' => [
//            '5RPX2frgjtPtR1FO24H7YqD8X59rZMBHgRN+4TYl+hJjo8pEpgTQvp0WMmV8DZNEVZB',
//            '5RPX2frgjtPtR1FO2asdwqwewqe+4TYl+hJjo8pEpgTQvp0WMmV8DZNEVZB',
//          ],
//        ],
//      ],
//    ],
//  ];

  // Create changes.
  \Civi\Api4\ShareChange::create(FALSE)
    ->addValue('change_type', 'civishare.change.contact.base')
    ->addValue('change_date', '2024-12-09T13:13:29+01:00')
    ->addValue('status', \Civi\Share\Change::STATUS_LOCAL)
    ->addValue('local_contact_id', 312)
    ->addValue('source_node_id', $local_node['id'])
    ->addValue('data_before', [
      'first_name' => 'Karl',
    ])
    ->addValue('data_after', [
      'first_name' => 'Carl',
      'birth_date' => '2000-01-01',
    ])
    ->execute();

  \Civi\Api4\ShareChange::create(FALSE)
    ->addValue('change_type', 'civishare.change.contact.base')
    ->addValue('change_date', '2024-12-09T13:13:29+01:00')
    ->addValue('status', \Civi\Share\Change::STATUS_LOCAL)
    ->addValue('local_contact_id', 2312)
    ->addValue('source_node_id', $local_node['id'])
    ->addValue('data_before', [
      'first_name' => 'Karlotta',
    ])
    ->addValue('data_after', [
      'first_name' => 'Escarlata',
      'last_name' => 'La Pirata',
    ])
    ->execute();

  // Send changes.
  \Civi\Api4\ShareChangeMessage::send()
    ->setSourceNodeId($local_node['id'])
    ->execute();

  return civicrm_api3_create_success($result);
}
