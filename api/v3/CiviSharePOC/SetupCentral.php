<?php

use CRM_Share_ExtensionUtil as E;

function _civicrm_api3_civi_share_p_o_c_setup_central_spec(&$spec) {
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
  $spec['shared_secret_intermediate'] = [
    'title'       => 'Intermediate Node Shared Secret',
    'description' => 'The shared secret to use for peering with the intermediate node.',
    'required'    => FALSE,
    'type'        => CRM_Utils_Type::T_STRING,
  ];
  $spec['shared_secret_leaf'] = [
    'title'       => 'Leaf Node Shared Secret',
    'description' => 'The shared secret to use for peering with the leaf node.',
    'required'    => FALSE,
    'type'        => CRM_Utils_Type::T_STRING,
  ];
}

function civicrm_api3_civi_share_p_o_c_setup_central(&$params) {
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
  $centralNode = \Civi\Api4\ShareNode::create(FALSE)
    ->addValue('name', 'Central Instance')
    ->addValue('short_name', 'central')
    ->addValue('is_local', TRUE)
    ->addValue('description', 'Automated test node representing the central instance.')
    ->addValue('is_enabled', TRUE)
    ->addValue('receive_identifiers', CRM_Utils_Array::implodePadded([]))
    ->addValue('send_identifiers', CRM_Utils_Array::implodePadded([]))
    ->execute()
    ->single();

  // Create a remote node representing the intermediate instance.
  $intermediateNode = \Civi\Api4\ShareNode::create(FALSE)
    ->addValue('name', 'Intermediate Instance')
    ->addValue('short_name', 'intermediate')
    ->addValue('description', 'Automated test node representing the intermediate instance.')
    ->addValue('is_local', FALSE)
    ->addValue('is_enabled', TRUE)
    ->addValue('rest_url', $credentials['intermediate']['url'])
    ->addValue('api_key', $credentials['intermediate']['api_key'])
    ->addValue('auth_key', $credentials['intermediate']['site_key'])
    ->execute()
    ->single();

  // Create a remote node representing the leaf instance.
  $leafNode = \Civi\Api4\ShareNode::create(FALSE)
    ->addValue('name', 'Leaf Instance')
    ->addValue('short_name', 'leaf')
    ->addValue('description', 'Automated test node representing the leaf instance.')
    ->addValue('is_local', FALSE)
    ->addValue('is_enabled', TRUE)
    ->addValue('rest_url', $credentials['leaf']['url'])
    ->addValue('api_key', $credentials['leaf']['api_key'])
    ->addValue('auth_key', $credentials['leaf']['site_key'])
    ->execute()
    ->single();

  // Create peering between central and intermediate instances.
  $sharedSecretIntermediate = !empty($params['shared_secret_intermediate'])
    ? $params['shared_secret_intermediate']
    : base64_encode(random_bytes(32));
  $nodePeeringIntermediate = \Civi\Api4\ShareNodePeering::create(FALSE)
    ->addValue('local_node', $centralNode['id'])
    ->addValue('remote_node', $intermediateNode['id'])
    ->addValue('is_enabled', TRUE)
    ->addValue('shared_secret', $sharedSecretIntermediate)
    ->execute()
    ->single();

  // Create peering between central and leaf instances.
  $sharedSecretLeaf = !empty($params['shared_secret_leaf'])
    ? $params['shared_secret_leaf']
    : base64_encode(random_bytes(32));
  $nodePeeringLeaf = \Civi\Api4\ShareNodePeering::create(FALSE)
    ->addValue('local_node', $centralNode['id'])
    ->addValue('remote_node', $leafNode['id'])
    ->addValue('is_enabled', TRUE)
    ->addValue('shared_secret', $sharedSecretLeaf)
    ->execute()
    ->single();


  // set the awoshare mode to 'Bundesverband'
  \Civi::settings()->set('awo_share_node_type', 'root');

  return civicrm_api3_create_success([
    'shared_secret_intermediate' => $sharedSecretIntermediate,
    'shared_secret_leaf' => $sharedSecretLeaf,
  ]);
}
