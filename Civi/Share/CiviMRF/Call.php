<?php

namespace Civi\Share\CiviMRF;

use CMRF\Core\AbstractCall;
use CMRF\Core\Call as CallInterface;

class Call extends AbstractCall implements CallInterface {

  protected string $request_entity;

  protected string $request_action;

  protected array $request;

  protected ?array $reply = NULL;

  protected string $status = CallInterface::STATUS_INIT;

  protected array $metadata = [];

  public static function createNew($connector_id, $core, $entity, $action, $parameters, $options, $callbacks, $factory, string $api_version) {
    if (!is_array($callbacks)) {
      if (NULL === $callbacks) {
        $callbacks = [];
      }
      else {
        $callbacks = [$callbacks];
      }
    }

    return static::create($connector_id, $core, $api_version, $entity, $action, $parameters, $options ?? [], $callbacks,
      $factory
    );
  }

  protected static function create(
    string $connector_id,
    Core $core,
    string $api_version,
    string $entity,
    string $action,
    array $parameters,
    array $options,
    array $callbacks,
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
    $call->request_entity = $entity;
    $call->request_action = $action;
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

  public function getEntity() {
    return $this->request_entity;
  }

  public function getAction() {
    return $this->request_action;
  }

  public function getParameters() {
    return $this->extractParameters($this->request);
  }

  public function getRequest() {
    return $this->request;
  }

  public function getOptions() {
    return $this->extractOptions($this->request);
  }

  public function getStatus() {
    return $this->status;
  }

  public function setStatus($status, $error_message, $error_code = NULL) {
    $error = [
      'is_error' => '1',
      'error_message' => $error_message,
      'error_code' => $error_code,
    ];

    $this->status = $status;
    $this->reply = $error;
    $this->reply_date = new \DateTime();

    $this->factory->update($this);
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

  public function getMetadata() {
    return $this->metadata;
  }

  public function getReply() {
    return $this->reply;
  }

  public function setReply($data, $newstatus) {
    $this->reply = $data;
    $this->reply_date = new \DateTime();
    $this->status = $newstatus;
    $this->factory->update($this);
    //    $this->checkAndTriggerFailure();
    //    $this->checkAndTriggerDone();
  }

  public function triggerCallback() {
    // TODO: Implement triggerCallback() method.
  }

}
