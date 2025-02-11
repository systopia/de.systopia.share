<?php

namespace Civi\Share\CiviMRF;

use Civi\Core\Service\AutoServiceInterface;
use Civi\Core\Service\AutoServiceTrait;
use Civi\Share\Message;

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

  /**
   * @return array<mixed>
   */
  public function sendMessage(int $shareNodePeeringId, Message $message): array {
    return $this->civiMRFClient
      ->init((string) $shareNodePeeringId)
      ->executeV4('ShareChangeMessage', 'receive', [
      'message' => $message->serialize(),
    ]);
  }

}
