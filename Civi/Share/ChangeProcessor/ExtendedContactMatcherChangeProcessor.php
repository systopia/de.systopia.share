<?php
namespace Civi\Share\ChangeProcessor;

use Civi\Core\Event\GenericHookEvent as Event;
use Civi\Api4\ShareChange;
use Civi\Api4\ShareNode;

class ExtendedContactMatcherChangeProcessor extends ChangeProcessorBase
{
  /**
   * Process the given change if you can/should/want and don't forget to mark processed when done
   *
   * @param $processing_event
   * @param $event_type
   * @param $dispatcher
   * @return void
   */
  public function process_change($processing_event, $event_type, $dispatcher)
  {
    // nothing to do here
    if ($processing_event->isProcessed()) return;

    // check if this is the one we're looking for
    if (!$processing_event->hasChangeType('civishare.change.contact.base')) return;

    // do the processing!
    $data_before = $processing_event->getChangeDataBefore();
    $data_after = $processing_event->getChangeDataAfter();
    $remote_contact_id = $processing_event->getContactID();

    // use peering service to find local_contact_id
    // @todo migrate peering to service
    $peering = new \Civi\Share\IdentityTrackerContactPeering();
    $change = $processing_event->getChange();
    $change_data = $processing_event->getChangeDataAfter();
    $local_contact_id = $peering->getLocalContactId($remote_contact_id, $change['source_node_id'], $change['local_node_id']);
    if ($local_contact_id) {
      $change_data['contact_id'] = $local_contact_id;
    }

    // basically: process the data by applying to the given local contact:
    try {
      $processing_event->logProcessingMessage("Creating/Updating contact: " . json_encode($change_data));
      \civicrm_api3('Contact', 'create', $change_data);
      $processing_event->setProcessed();
    } catch (\Exception $ex) {
      $processing_event->logProcessingMessage("Creating/Updating contact failed: " . $ex->getMessage());
    }
  }
}
