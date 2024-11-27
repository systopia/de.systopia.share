<?php

namespace Civi\Share\CiviMRF;

use Civi\Api4\ShareNode;

class Profile {

  private int $shareNodeId;

  private string $url;

  private string $apiKey;

  private string $siteKey;

  public function __construct(int $shareNodeId, string $url, string $apiKey, string $siteKey) {
    $this->shareNodeId = $shareNodeId;
    $this->url = $url;
    $this->apiKey = $apiKey;
    $this->siteKey = $siteKey;
  }

  public static function load(int $shareNodeId) {
    return self::loadMultiple([$shareNodeId])[$shareNodeId];
  }

  public static function loadMultiple(array $shareNodeIds = NULL): array {
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
      $profiles[$shareNodeId] = new self($shareNode['id'], $shareNode['rest_url'], $shareNode['api_key'], $shareNode['site_key']);
    }
    return $profiles;
  }

}
