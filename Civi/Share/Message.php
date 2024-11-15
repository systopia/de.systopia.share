<?php
namespace Civi\Share;

use Civi\Api4\ShareChange;

/**
 * CiviShare Message object (transient)
 *
 * A message object contains a series of ShareChange ids
 *  along with metadata
 *
 * This class provides functions to
 *  - serialise/encrypt
 *  - deserialise/decrypt
 *  - send
 *  - verify
 *  - loop detection
 *
 * Provided by the CiviShare extension.
 *
 * @package Civi\Api4
 */
class Message {

  /** @var array list of change ids */
  protected array $change_ids = [];

  /**
   * Create a new, empty message
   */
  public function __construct()
  {

  }

  /**
   * Add a change ID to the message
   *
   * @param int $change_id
   *
   * @return void
   */
  public function addChangeId(int $change_id)
  {
    $this->change_ids[] = $change_id;
  }

  /**
   * Send out a compiled message
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function send()
  {
    $lock = \Civi::lockManager()->acquire('de.systopia.share.send');

    // first: get the source node, and then find nodes to send these changes to
    



    // then send all changes there

    //

    $lock->release();
  }

  /**
   * Parse/reconstruct a message
   *
   * @param string $data
   *   a raw data packed (encrypted and serialised)
   *
   * @param int node_peering ID
   *   the ID of an active peering object
   *
   * @return ?Message
   */
  public static function extractSerialisedMessage($data, $node_peering_id) : ?Message
  {

  }

}
