<?php
use CRM_Share_ExtensionUtil as E;
return [
  'name' => 'ShareChange',
  'table' => 'civicrm_share_change',
  'class' => 'CRM_Share_DAO_ShareChange',
  'getInfo' => fn() => [
    'title' => E::ts('ShareChange'),
    'title_plural' => E::ts('ShareChanges'),
    'description' => E::ts('CiviShare.Change: data structure to record changes'),
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
    'change_id' => [
      'title' => E::ts('Change ID'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => E::ts('Network wide unique change ID'),
    ],
    'change_group_id' => [
      'title' => E::ts('Change group ID'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => E::ts('Changes can be batched into groups, so that they are to be processed in one go'),
    ],
    'status' => [
      'title' => E::ts('Status'),
      'sql_type' => 'varchar(8)',
      'input_type' => 'Text',
      'description' => E::ts('Status: LOCAL, PENDING, BUSY, FORWARD, DONE, DROPPED, ERROR'),
    ],
    'hash' => [
      'title' => E::ts('Name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => E::ts('SHA1 hash of the change to detect duplicates and loops'),
    ],
    'handler_class' => [
      'title' => E::ts('Handler class'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => E::ts('Name of the handler class that produced this change'),
    ],
    'local_contact_id' => [
      'title' => E::ts('Local contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to the local contact ID'),
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'source_node_id' => [
      'title' => E::ts('Source node ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to node ID to civicrm_share_node where the change came from'),
      'entity_reference' => [
        'entity' => 'ShareNode',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'change_date' => [
      'title' => E::ts('Change date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'required' => TRUE,
      'description' => E::ts('Timestamp of the change'),
    ],
    'received_date' => [
      'title' => E::ts('Received date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'required' => TRUE,
      'description' => E::ts('Timestamp of the reception of the change'),
    ],
    'processed_date' => [
      'title' => E::ts('Processed date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'description' => E::ts('Timestamp of the processing of the change'),
    ],
    'triggerd_by' => [
      'title' => E::ts('Triggerd By'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('List of change_ids that triggered this change'),
    ],
    'data_before' => [
      'title' => E::ts('Data before'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('The data before the change'),
    ],
    'data_after' => [
      'title' => E::ts('Data after'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('The data after the change'),
    ],
  ],
  'getIndices' => fn() => [
    'UI_change_id' => [
      'fields' => [
        'change_id' => TRUE,
      ],
      'unique' => TRUE,
    ],
    'index_change_group_id' => [
      'fields' => [
        'change_group_id' => TRUE,
      ],
    ],
    'index_hash' => [
      'fields' => [
        'hash' => TRUE,
      ],
    ],
    'index_change_date' => [
      'fields' => [
        'change_date' => TRUE,
      ],
    ],
  ],
  'getPaths' => fn() => [],
];
