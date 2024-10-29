<?php
use CRM_Share_ExtensionUtil as E;
return [
  'name' => 'ShareNode',
  'table' => 'civicrm_share_node',
  'class' => 'CRM_Share_DAO_ShareNode',
  'getInfo' => fn() => [
    'title' => E::ts('ShareNode'),
    'title_plural' => E::ts('ShareNodes'),
    'description' => E::ts('CiviShare.Node: represents a node in the network, remote or local'),
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
    'name' => [
      'title' => E::ts('Name'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => E::ts('Name of the node'),
    ],
    'short_name' => [
      'title' => E::ts('Short name'),
      'sql_type' => 'varchar(16)',
      'input_type' => 'Text',
      'description' => E::ts('Short name identifier'),
    ],
    'is_local' => [
      'title' => E::ts('Is local?'),
      'sql_type' => 'tinyint(1)',
      'input_type' => 'CheckBox',
      'description' => E::ts('Is this node representing this system or a remote one?'),
    ],
    'description' => [
      'title' => E::ts('Description'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => E::ts('Description to clarify what/where that node is'),
    ],
    'rest_url' => [
      'title' => E::ts('Rest URL'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => E::ts('URL of the REST API of the node'),
    ],
    'api_key' => [
      'title' => E::ts('API key'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => E::ts('API_KEY of the node'),
    ],
    'is_enabled' => [
      'title' => E::ts('Is enabled?'),
      'sql_type' => 'tinyint(1)',
      'input_type' => 'CheckBox',
      'description' => E::ts('Is this node enabled?'),
    ],
    'auth_key' => [
      'title' => E::ts('Auth key'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => E::ts('Bi-directional shared-secret authorisation key'),
    ],
    'receive_identifiers' => [
      'title' => E::ts('Receive identifiers'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('Defines what data identifiers that will be received by this node'),
    ],
    'send_identifiers' => [
      'title' => E::ts('Send identifiers'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('Defines what data identifiers that will be sent by this nodes'),
    ],
  ],
  'getIndices' => fn() => [
    'UI_short_name' => [
      'fields' => [
        'short_name' => TRUE,
      ],
      'unique' => TRUE,
    ],
    'UI_auth_key' => [
      'fields' => [
        'auth_key' => TRUE,
      ],
      'unique' => TRUE,
    ],
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/share/node/add?reset=1',
    'view' => 'civicrm/share/node/search?reset=1&action=view&id=[id]',
    'update' => 'civicrm/share/node/edit?reset=1&action=update&id=[id]',
    'delete' => 'civicrm/share/node/edit?reset=1&action=delete&id=[id]',
    'browse' => 'civicrm/share/node',

  ],
];
