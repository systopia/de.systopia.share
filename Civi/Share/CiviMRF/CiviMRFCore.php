<?php

namespace Civi\Share\CiviMRF;

use Civi\Api4\ShareNode;
use Civi\Api4\ShareNodePeering;
use Civi\Core\Service\AutoServiceInterface;
use Civi\Core\Service\AutoServiceTrait;
use CMRF\Core\Connection;
use CMRF\Exception\ProfileNotFoundException;
use CMRF\PersistenceLayer\CallFactory;

/**
 * @service civi.share.civimrf_core
 * @internal
 */
class CiviMRFCore extends \CMRF\Core\Core implements AutoServiceInterface {

  use AutoServiceTrait;

  public function __construct() {
    parent::__construct(new CallFactory(
      ['\Civi\Share\CiviMRF\CiviMRFCall', 'createNew'],
      ['\Civi\Share\CiviMRF\CiviMRFCall', 'createWithRecord']
    ));
  }

  protected function getConnection($connector_id): Connection {
    return new \CMRF\Connection\CurlAuthX($this, $connector_id);
  }

  public function getConnectionProfiles() {
    $profiles = [];
    $query = ShareNode::get()
      ->addSelect('id', 'rest_url', 'api_key', 'site_key');
    // TODO: only enabled?
    if (isset($shareNodeIds)) {
      $query
        ->addWhere('id', 'IN', $shareNodeIds);
    }
    foreach ($query
      ->execute()
      ->indexBy('id') as $shareNodeId => $shareNode) {
      $profiles[$shareNodeId] = [
        'id' => $shareNode['id'],
        'urlV4' => $shareNode['rest_url'],
        'api_key' => $shareNode['api_key'],
        'site_key' => $shareNode['site_key'],
      ];
    }
    return $profiles;
  }

  public function getDefaultProfile() {
    // CiviShare does not support default profiles.
    return NULL;
  }

  protected function getRegisteredConnectors() {
    $connectors = [];
    $query = ShareNodePeering::get()
      ->addSelect('id', 'remote_node');
    // TODO: only enabled?
    if (isset($shareNodePeeringIds)) {
      $query
        ->addWhere('id', 'IN', $shareNodePeeringIds);
    }
    $shareNodeIds = $query
      ->execute()
      ->indexBy('id')
      ->column('remote_node');
    foreach ($shareNodeIds as $shareNodePeeringId => $shareNodeId) {
      $connectors[$shareNodePeeringId] = [
        'id' => $shareNodePeeringId,
        // TODO: Copy ID?
        'type' => '',
        'profile' => $shareNodeId,
      ];
    }
    return $connectors;
  }

  public function registerConnector($connector_id, $profile = NULL) {
    // Not supported. Connectors are represented by ShareNodePeering entities.
  }

  protected function storeRegisteredConnectors($connectors) {
    // Not supported. Connectors are represented by ShareNodePeering entities.
  }

  protected function getSettings() {
    // No settings to retrieve.
  }

  protected function storeSettings($settings) {
    // No settings to store.
  }

}
