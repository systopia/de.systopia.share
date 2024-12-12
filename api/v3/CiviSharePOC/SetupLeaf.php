<?php

use CRM_Share_ExtensionUtil as E;

function _civicrm_api3_civi_share_p_o_c_setup_leaf_spec(&$spec) {
  $spec['clear'] = [
    'title'       => 'Clear CiviShare entities',
    'description' => 'Whether to delete all CiviShare entities before creating configuration.',
    'required'    => FALSE,
    'type'        => CRM_Utils_Type::T_BOOLEAN,
  ];
  $spec['config_file_path'] = [
    'title'       => 'Configuration file path',
    'description' => 'Path to an INI-formatted configuration file for REST endpoint credentials.',
    'required'    => TRUE,
    'type'        => CRM_Utils_Type::T_STRING,
  ];
  $spec['shared_secret_central'] = [
    'title'       => 'Central Node Shared Secret',
    'description' => 'The shared secret to use for peering with the central node.',
    'required'    => FALSE,
    'type'        => CRM_Utils_Type::T_STRING,
  ];
}

function civicrm_api3_civi_share_p_o_c_setup_leaf(&$params) {
  $credentials = parse_ini_file($params['config_file_path'], TRUE);
  if (FALSE === $credentials) {
    return civicrm_api3_create_error(E::ts('Failed loading credentials file.'));
  }

  if ($params['clear'] ?? FALSE) {
    // Delete existing peerings and nodes.
    \Civi\Api4\ShareNodePeering::delete(FALSE)
      ->addWhere('id', 'IS NOT NULL')
      ->execute();
    \Civi\Api4\ShareNode::delete(FALSE)
      ->addWhere('id', 'IS NOT NULL')
      ->execute();

    // Delete existing changes.
    \Civi\Api4\ShareChange::delete(FALSE)
      ->addWhere('id', 'IS NOT NULL')
      ->execute();
  }

  // Create a local node representing the central instance.
  $leafNode = \Civi\Api4\ShareNode::create(FALSE)
    ->addValue('name', 'Leaf Instance')
    ->addValue('short_name', 'leaf')
    ->addValue('is_local', TRUE)
    ->addValue('description', 'Automated test node representing the leaf instance.')
    ->addValue('is_enabled', TRUE)
    ->addValue('receive_identifiers', CRM_Utils_Array::implodePadded([]))
    ->addValue('send_identifiers', CRM_Utils_Array::implodePadded([]))
    ->execute()
    ->single();

  // Create a remote node representing the central instance.
  $centralNode = \Civi\Api4\ShareNode::create(FALSE)
    ->addValue('name', 'Central Instance')
    ->addValue('short_name', 'central')
    ->addValue('description', 'Automated test node representing the central instance.')
    ->addValue('is_local', FALSE)
    ->addValue('is_enabled', TRUE)
    ->addValue('rest_url', $credentials['central']['url'])
    ->addValue('api_key', $credentials['central']['api_key'])
    ->addValue('auth_key', $credentials['central']['site_key'])
    ->execute()
    ->single();

  // Create peering between leaf and central instances.
  $sharedSecretCentral = !empty($params['shared_secret_central'])
    ? $params['shared_secret_central']
    : base64_encode(random_bytes(32));
  $nodePeeringCentral = \Civi\Api4\ShareNodePeering::create(FALSE)
    ->addValue('local_node', $leafNode['id'])
    ->addValue('remote_node', $centralNode['id'])
    ->addValue('is_enabled', TRUE)
    ->addValue('shared_secret', $sharedSecretCentral)
    ->execute()
    ->single();

  return civicrm_api3_create_success([
    'shared_secret_central' => $sharedSecretCentral,
  ]);
}
