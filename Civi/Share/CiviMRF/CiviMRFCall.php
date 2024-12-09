<?php

namespace Civi\Share\CiviMRF;

use CMRF\Core\AbstractCall;
use CMRF\Core\Call as CallInterface;
use CMRF\PersistenceLayer\CallFactory;

class CiviMRFCall extends AbstractCall {

  protected string $requestEntity;

  protected string $requestAction;

  protected array $request;

  protected ?array $reply = NULL;

  protected string $status = CallInterface::STATUS_INIT;

  protected array $metadata = [];

  public static function createNew(
    string $connector_id,
    CiviMRFCore $core,
    string $entity,
    string $action,
    array $parameters,
    ?array $options,
    ?array $callbacks,
    CallFactory $factory,
    string $api_version
  ): self {
    if (!is_array($callbacks)) {
      if (NULL === $callbacks) {
        $callbacks = [];
      }
      else {
        $callbacks = [$callbacks];
      }
    }

    return static::create(
      $connector_id,
      $core,
      $api_version,
      $entity,
      $action,
      $parameters,
        $options ?? [],
      $callbacks,
      $factory
    );
  }

  protected static function create(
    string $connector_id,
    CiviMRFCore $core,
    string $api_version,
    string $entity,
    string $action,
    array $parameters,
    ?array $options,
    ?array $callbacks,
    CallFactory $factory
  ): self {
    $call = new self($core, $connector_id, $factory);

    // compile request
    if ('3' === $api_version) {
      $call->request = $call->compileRequest($parameters, $options);
      $call->request['entity'] = $entity;
      $call->request['action'] = $action;
    }
    elseif ('4' === $api_version) {
      $call->request = $parameters;
    }
    $call->requestEntity = $entity;
    $call->requestAction = $action;
    $call->request['version'] = $api_version;
    $call->status = CallInterface::STATUS_INIT;
    $call->metadata['callbacks'] = $callbacks;
    $call->callbacks = $callbacks;

    return $call;
  }

  public static function createWithRecord($connector_id, $core, $record, $factory) {
    // Persisting calls not implemented.
    return NULL;
  }

  public function getApiVersion(): string {
    return '4';
  }

  public function getEntity(): string {
    return $this->requestEntity;
  }

  public function getAction(): string {
    return $this->requestAction;
  }

  public function getParameters(): array {
    return $this->extractParameters($this->request);
  }

  public function getRequest(): array {
    return $this->request;
  }

  public function getOptions(): array {
    return $this->extractOptions($this->request);
  }

  public function getStatus(): string {
    return $this->status;
  }

  /**
   * @param string $status
   * @param string $error_message
   * @param ?string $error_code
   */
  public function setStatus($status, $error_message, $error_code = NULL): void {
    $error = [
      'is_error' => '1',
      'error_message' => $error_message,
      'error_code' => $error_code,
    ];

    $this->status = $status;
    $this->reply = $error;
    $this->reply_date = new \DateTime();

    $this->factory->update($this);
    // TODO: Revisit after POC phase.
    //    $this->checkAndTriggerFailure();
    //    $this->checkAndTriggerDone();
  }

  /**
   * @inheritDoc
   */
  public function getCachedUntil() {
    // Not implemented.
    return NULL;
  }

  public function getMetadata(): array {
    return $this->metadata;
  }

  public function getReply(): ?array {
    return $this->reply;
  }

  /**
   * @param array $data
   * @param string $newstatus
   */
  public function setReply($data, $newstatus): void {
    $this->reply = $data;
    $this->reply_date = new \DateTime();
    $this->status = $newstatus;
    $this->factory->update($this);
    // TODO: Revisit after POC phase.
    //    $this->checkAndTriggerFailure();
    //    $this->checkAndTriggerDone();
  }

  public function triggerCallback() {
    // TODO: Implement triggerCallback() method.
  }

}
