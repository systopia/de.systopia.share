<?php

declare(strict_types = 1);

namespace tests\phpunit\Civi\Share\Mock\CiviMRF;

use Civi\Api4\ShareNodePeering;
use Civi\Share\Message;

/**
 * @service civi.share.mock.api
 *
 * Mock service for the CiviShare CiviMRF API which uses a local-to-local shortcut if the node peering does not include
 * remote information.
 */
class ShareApi extends \Civi\Share\CiviMRF\ShareApi {

  public function sendMessage(int $shareNodePeeringId, Message $message): array {
    $peering = ShareNodePeering::get(FALSE)
      ->addSelect('id', 'remote_node', 'remote_node.*')
      ->addWhere('id', '=', $shareNodePeeringId)
      ->execute();
    if (empty($peering['remote_node.rest_url']) || empty($peering['remote_node.api_key'])) {
      if (defined('CIVISHARE_ALLOW_LOCAL_LOOP')) {
        $message->processOnNode($peering['remote_node.id']);
      }
    }
    else {
      return parent::sendMessage($shareNodePeeringId, $message);
    }
  }

}
