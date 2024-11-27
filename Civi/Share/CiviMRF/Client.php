<?php

namespace Civi\Share\CiviMRF;

use Civi\Core\Service\AutoService;
use CMRF\Core\Call;
use CMRF\Exception\ApiCallFailedException;

/**
 * @service civi.share.civimrf
 */
class Client extends AutoService {

  private Core $cmrfCore;

  private string $connectorId;

  public function __construct() {
    $this->cmrfCore = new Core();
  }

  public function init($connectorId) {
    $this->connectorId = $connectorId;
    return $this;
  }

  public function getConnectorId() {
    if (!isset($this->connectorId)) {
      throw new \RuntimeException('CiviShare CiviMRF Client not initialized.');
    }
    return $this->connectorId;
  }

  public function executeV3(string $entity, string $action, array $parameters = [], array $options = []): array {
    $call = $this->cmrfCore->createCallV3($this->getConnectorId(), $entity, $action, $parameters, $options);

    $result = $this->cmrfCore->executeCall($call);
    if (NULL === $result || Call::STATUS_FAILED === $call->getStatus()) {
      throw ApiCallFailedException::fromCall($call);
    }

    return $result;
  }

  public function executeV4(string $entity, string $action, array $parameters = []): array {
    $call = $this->cmrfCore->createCallV4($this->getConnectorId(), $entity, $action, $parameters);

    $result = $this->cmrfCore->executeCall($call);
    if (NULL === $result || Call::STATUS_FAILED === $call->getStatus()) {
      throw ApiCallFailedException::fromCall($call);
    }

    return $result;
  }

}
