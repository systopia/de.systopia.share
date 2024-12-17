<?php
namespace Civi\Share;

use CRM_Identitytracker_ExtensionUtil as E;

use Civi\Api4\ShareChange;
use Civi\Api4\ShareNode;
use Civi\Share\ChangeProcessingEvent;

const CIVISHARE_IDTRACKER_TYPE = 'civishare_contact_id';

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
    // TODO: This should be a managed entity.
    \CRM_Identitytracker_Configuration::add_identity_type(CIVISHARE_IDTRACKER_TYPE, E::ts("CiviShare Contact Linking"));
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
   *    the ID of the node representing hosting the remote contact
   *
   * @param ?int $local_contact_node_id
   *    the ID of the local node. Will default to *the* local node
   */
  public function peer($remote_contact_id, $local_contact_id, $remote_contact_node_id, $local_contact_node_id = null)
  {
    // get the remote node's short name  TODO: cache?
    $remote_node = civicrm_api4('ShareNode', 'get', [
      'select' => ['short_name'],
      'where' => [['id', '=', $remote_contact_node_id]],
    ])->first();

    // create ID string
    $remote_identifier = "{$remote_node['short_name']}-{$remote_contact_id}";

    // add store with contact
    \civicrm_api3('Contact', 'addidentity', [
      'contact_id' => $local_contact_id,
      'identifier' => $remote_identifier,
      'identifier_type' => CIVISHARE_IDTRACKER_TYPE,
    ]);
  }


  /**
   * This method gives you the local contact ID based on a remote contact ID and the associate node.
   *
   * @param int $remote_contact_id
   *   the contact ID of the remote contact, i.e. the contact ID on the remote system
   *
   * @param int $remote_contact_node_id
   *    the ID of the remote node
   *
   * @param ?int $local_contact_node_id
   *    the ID of the local node. Will default to *the* local node
   */
  public function getLocalContactId($remote_contact_id,  $remote_contact_node_id, $local_contact_node_id = null)
  {
    // get the remote node's short name  TODO: cache?
    $remote_node = civicrm_api4('ShareNode', 'get', [
      'select' => ['short_name'],
      'where' => [['id', '=', $remote_contact_node_id]],
    ])->first();

    // create ID string
    $remote_identifier = "{$remote_node['short_name']}-{$remote_contact_id}";

    // add store with contact
    $search_result = \civicrm_api3('Contact', 'findbyidentity', [
      //'contact_id' => $local_contact_id,
      'identifier' => $remote_identifier,
      'identifier_type' => CIVISHARE_IDTRACKER_TYPE,
    ]);

    return $search_result['id'] ?? null;
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
    throw new \Exception("not implemented");
  }
}
