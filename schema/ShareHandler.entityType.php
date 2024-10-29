<?php
use CRM_Share_ExtensionUtil as E;
return [
  'name' => 'ShareHandler',
  'table' => 'civicrm_share_handler',
  'class' => 'CRM_Share_DAO_ShareHandler',
  'getInfo' => fn() => [
    'title' => E::ts('ShareHandler'),
    'title_plural' => E::ts('ShareHandlers'),
    'description' => E::ts('CiviShare.Handler: handlers implement the data processing'),
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
      'description' => E::ts('Human-readable name of this handler instance'),
    ],
    'class' => [
      'title' => E::ts('Class'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => E::ts('Name of the implementing class'),
    ],
    'weight' => [
      'title' => E::ts('Weight'),
      'sql_type' => 'int',
      'input_type' => 'Number',
      'description' => E::ts('Defines the order of the handlers'),
    ],
    'is_enabled' => [
      'title' => E::ts('Is enabled?'),
      'sql_type' => 'tinyint(1)',
      'input_type' => 'CheckBox',
      'description' => E::ts('Is this handler enabled?'),
    ],
    'configuration' => [
      'title' => E::ts('Configuration'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'serialize' => CRM_Core_DAO::SERIALIZE_JSON,
      'description' => E::ts('JSON data that defines what data will be sent to this node'),
    ],
  ],
  'getIndices' => fn() => [
    'index_is_enabled' => [
      'fields' => [
        'is_enabled' => TRUE,
      ],
    ],
    'index_weight' => [
      'fields' => [
        'weight' => TRUE,
      ],
    ],
  ],
  'getPaths' => fn() => [],
];
