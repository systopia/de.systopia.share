<?php
use CRM_Share_ExtensionUtil as E;
return [
  'name' => 'ShareNodePeering',
  'table' => 'civicrm_share_node_peering',
  'class' => 'CRM_Share_DAO_ShareNodePeering',
  'getInfo' => fn() => [
    'title' => E::ts('ShareNodePeering'),
    'title_plural' => E::ts('ShareNodePeerings'),
    'description' => E::ts('CiviShare.NodePeering: peering of two nodes'),
    'log' => TRUE,
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'local_node' => [
      'title' => E::ts('Local node'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => E::ts('local node ID - reference to civicrm_share_node'),
      'entity_reference' => [
        'entity' => 'ShareNode',
        'is_local' => 1,
        'key' => 'id',
      ],
    ],
    'remote_node' => [
      'title' => E::ts('Remote node'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => E::ts('Remote node ID - reference to civicrm_share_node'),
      'entity_reference' => [
        'entity' => 'ShareNode',
        'key' => 'id',
        'is_local' => 0,
      ],
    ],
    'is_enabled' => [
      'title' => E::ts('Is enabled?'),
      'sql_type' => 'tinyint(1)',
      'input_type' => 'CheckBox',
      'description' => E::ts('Is this peering enabled?'),
    ],
    'shared_secret' => [
      'title' => E::ts('Shared secret'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => E::ts('Bi-directional shared-secret'),
    ],
  ],
  'getIndices' => fn() => [
    'UI_local_node' => [
      'fields' => [
        'local_node' => TRUE,
      ],
      'unique' => TRUE,
    ],
    'UI_remote_node' => [
      'fields' => [
        'remote_node' => TRUE,
      ],
      'unique' => TRUE,
    ],
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/share/node/peering/add?reset=1&action=add',
    'update' => 'civicrm/share/node/peering/add?reset=1&action=update&id=[id]',
    'delete' => 'civicrm/share/node/peering/add?reset=1&action=delete&id=[id]',
    'browse' => 'civicrm/share/node/peering',
  ],
];
