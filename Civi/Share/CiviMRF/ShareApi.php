<?php

namespace Civi\Share\CiviMRF;

class ShareApi {

  protected Client $client;

  public function __construct(Client $client) {
    $this->client = $client;
  }

  public static function create(Client $client) {
    return new self($client);
  }

  public function sendMessage(string $message) {
    $result = $this->client->executeV4('ShareMessage', 'receive', [
      'message' => $message,
    ]);
  }

}
