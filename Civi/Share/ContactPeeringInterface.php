<?php
namespace Civi\Share;

use Civi\Api4\ShareChange;
use Civi\Api4\ShareNode;
use \Civi\Share\ChangeProcessingEvent;
use Civi\Funding\Event\FundingCase\GetPossibleFundingCaseStatusEvent;


/**
 * Interface definition for for contact peering, i.e. the link between two (or more) contacts
 *   between different systems (nodes)
 */
interface ContactPeeringInterface
{
  /**
   * This method allows you connect (peer) two contact Ids
   *
   * @param int $remote_contact_id
   *   the contact ID of the remote contact
   *
   * @param int $local_contact_id
   *    the local contact ID
   *
   * @param int $remote_contact_node_id
   *    the ID of the remote node
   *
   * @param ?int $local_contact_node_id
   *    the ID of the local node. Will default to *the* local node
   */
  public function peer($remote_contact_id, $local_contact_id, $remote_contact_node_id, $local_contact_node_id = null);


  /**
   * This method gives you the local contact ID based on a remote contact ID and the associate node.
   *
   * @param int $remote_contact_id
   *   the contact ID of the remote contact
   *
   * @param int $remote_contact_node_id
   *    the ID of the remote node
   *
   * @param ?int $local_contact_node_id
   *    the ID of the local node. Will default to *the* local node
   */
  public function getLocalContactId($remote_contact_id,  $remote_contact_node_id, $local_contact_node_id = null);


  /**
   * This method gives you the local contact ID based on a remote contact ID and the associate node.
   *   If the contact is not found locally, it is being created.
   *
   * @param int $remote_contact_id
   *   the contact ID of the remote contact
   *
   * @param int $remote_contact_node_id
   *    the ID of the remote node
   *
   * @param ?int $local_contact_node_id
   *    the ID of the local node. Will default to *the* local node
   */
  public function getOrCreateLocalContactId($remote_contact_id,  $remote_contact_node_id, $local_contact_node_id = null);
}
