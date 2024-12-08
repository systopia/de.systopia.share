<?php
namespace Civi\Share;

use CRM_Identitytracker_ExtensionUtil as E;

use Civi\Api4\ShareChange;
use Civi\Api4\ShareNode;
use Civi\Share\ChangeProcessingEvent;


/**
 * This class provides contact peering functionality based on the ID-Tracker extension
 */
class IdentityTrackerContactPeering implements ContactPeeringInterface
{
  /** @var string the ID Tracker type used to track IDs */
  static $share_id_tracker_type = null;

  /**
   * Constructor will check requirements
   */
  function __construct() {
    // make sure ID-Tracker is installed
    $ext_status = \CRM_Extension_System::singleton()->getManager()->getStatuses();
    if (!isset($ext_status['de.systopia.identitytracker']) || $ext_status['de.systopia.identitytracker'] != 'installed') {
      throw new \Exception("IdentityTracker extension is not installed, cannot use IdentityTrackerContactPeering.");
    }

    // make sure we have our CiviShare ID type
    \CRM_Identitytracker_Configuration::add_identity_type('civishare_contact_id', E::ts("CiviShare Contact Linking"));
  }


  /**
   * This method allows you to connect (peer) two contact Ids
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
  public function peer($remote_contact_id, $local_contact_id, $remote_contact_node_id, $local_contact_node_id = null)
  {
    


  }


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
  public function getLocalContactId($remote_contact_id,  $remote_contact_node_id, $local_contact_node_id = null)
  {

  }


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
  public function getOrCreateLocalContactId($remote_contact_id,  $remote_contact_node_id, $local_contact_node_id = null)
  {

  }
}
