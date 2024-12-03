<?php

namespace Civi\Share\CiviMRF;

use Civi\Core\Service\AutoServiceInterface;
use Civi\Core\Service\AutoServiceTrait;
use CMRF\Core\Call;
use CMRF\Exception\ApiCallFailedException;

/**
 * @service civi.share.civimrf_client
 * @internal
 */
class CiviMRFClient implements AutoServiceInterface {

  use AutoServiceTrait;

  /**
   * @var \Civi\Share\CiviMRF\CiviMRFCore
   * @inject civi.share.civimrf_core
   */
  protected $civiMRFCore;

  protected ?string $connectorId;

  public function init(string $connectorId): self {
    $this->connectorId = $connectorId;
    return $this;
  }

  public function getConnectorId(): string {
    if (!isset($this->connectorId)) {
      throw new \RuntimeException('CiviShare CiviMRF Client not initialized.');
    }
    return $this->connectorId;
  }

  public function executeV3(string $entity, string $action, array $parameters = [], array $options = []): array {
    $call = $this->civiMRFCore->createCallV3($this->getConnectorId(), $entity, $action, $parameters, $options);

    $result = $this->civiMRFCore->executeCall($call);
    if (NULL === $result || Call::STATUS_FAILED === $call->getStatus()) {
      throw ApiCallFailedException::fromCall($call);
    }

    return $result;
  }

  public function executeV4(string $entity, string $action, array $parameters = []): array {
    $call = $this->civiMRFCore->createCallV4($this->getConnectorId(), $entity, $action, $parameters);

    $result = $this->civiMRFCore->executeCall($call);
    if (NULL === $result || Call::STATUS_FAILED === $call->getStatus()) {
      throw ApiCallFailedException::fromCall($call);
    }

    return $result;
  }

}
