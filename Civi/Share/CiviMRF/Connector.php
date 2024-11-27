<?php

namespace Civi\Share\CiviMRF;

use Civi\Api4\ShareNodePeering;

class Connector {

  private int $shareNodePeeringId;

  private Profile $profile;

  public function __construct(int $shareNodePeeringId, Profile $profile) {
    $this->shareNodePeeringId = $shareNodePeeringId;
    $this->profile = $profile;
  }

  public static function load(string $shareNodePeeringId) {
    return self::loadMultiple([$shareNodePeeringId])[$shareNodePeeringId];
  }

  public static function loadMultiple(array $shareNodePeeringIds = NULL) {
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
      $profile = Profile::load($shareNodeId);
      $connectors[$shareNodePeeringId] = new self($shareNodePeeringId, $profile);
    }
    return $connectors;
  }

}
