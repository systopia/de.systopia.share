<?php

namespace Civi\Share\CiviMRF;

use Civi\Core\Service\AutoServiceInterface;
use Civi\Core\Service\AutoServiceTrait;

/**
 * @service civi.share.api
 */
class ShareApi implements AutoServiceInterface {

  use AutoServiceTrait;

  /**
   * @var \Civi\Share\CiviMRF\CiviMRFClient
   * @inject civi.share.civimrf_client
   */
  protected $civiMRFClient;

  public function sendMessage(string $shareNodePeeringId, array $message) {
    $result = $this->civiMRFClient
      ->init($shareNodePeeringId)
      ->executeV4('ShareChangeMessage', 'receive', [
      'message' => $message,
    ]);
  }

}
