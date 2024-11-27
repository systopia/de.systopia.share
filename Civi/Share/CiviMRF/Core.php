<?php

namespace Civi\Share\CiviMRF;

use Civi\Api4\ShareNodePeering;
use CMRF\Core\Connection;
use CMRF\Exception\ProfileNotFoundException;
use CMRF\PersistenceLayer\CallFactory;

class Core extends \CMRF\Core\Core {

  public function __construct() {
    parent::__construct(new CallFactory(
      ['\Civi\Share\CiviMRF\Call', 'createNew'],
      ['\Civi\Share\CiviMRF\Call', 'createWithRecord']
    ));
  }

  protected function getConnection($connector_id): Connection {
    return new \CMRF\Connection\CurlAuthX($this, $connector_id);
  }

  public function getConnectionProfiles() {
    return Profile::loadMultiple();
  }

  public function getDefaultProfile() {
    // CiviShare does not support default profiles.
    return NULL;
  }

  protected function getRegisteredConnectors() {
    return Connector::loadMultiple();
  }

  public function registerConnector($connector_id) {
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
