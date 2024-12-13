<?php
namespace Civi\Share\ChangeProcessor;

use Civi\Core\Event\GenericHookEvent as Event;
use Civi\Api4\ShareChange;
use Civi\Api4\ShareNode;

class DefaultContactBaseChangeProcessor extends ChangeProcessorBase
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

    // check if contact already known (i.e. peered)
    $local_contact_id = $processing_event->getLocalContactID();

    if (!$local_contact_id) { // not known
      // in this case we would want to identify the contact with the given attributes
      $identification_attribute_sets = $this->getConfigValue('contact_identifying_attribute_sets', [
          ['first_name', 'last_name', 'contact_type', 'email'],
          ['first_name', 'contact_type', 'email']
      ]);

      // now that we have the attribute sets for identification, iterate until we find a match
      foreach ($identification_attribute_sets as $attribute_set) {
        // search query
        $search_parameters = $this->buildSearchParameters($attribute_set, $processing_event->getChangeDataBefore());
        $processing_event->logProcessingMessage("Using attributes " . json_encode($attribute_set) . " to identifiy contact...");
        $result = \civicrm_api3('Contact', 'get', $search_parameters);
        if ($result['count'] == 1) {
          $local_contact_id = $result['id'];
          $processing_event->logProcessingMessage("Local contact {$local_contact_id} identified.");
          break;
        } else {
          $processing_event->logProcessingMessage("Local contact not be identified via attributes: " . json_encode($attribute_set));
        }
      }
    }

    if ($local_contact_id) {
      // found it, let's peer it if possible
      $remote_contact_id = (int)$processing_event->getRemoteContactID();
      if ($remote_contact_id) {
        $peering = new \Civi\Share\IdentityTrackerContactPeering();
        $change_data = $processing_event->getChange();
        $peering->peer($remote_contact_id, $local_contact_id, $change_data['source_node_id']);
      }

      // run a CONTACT UPDATE
      $update_entity = $this->getConfigValue('update_entity', 'Contact');
      $update_action = $this->getConfigValue('update_action', 'create');
      $update_data = $processing_event->getChangeDataAfter();
      $update_data['id'] = $local_contact_id;


      // basically: process the data by applying to the given local contact:
      try {
        $processing_event->logProcessingMessage("Creating/Updating contact: " . json_encode($change_data));
        \civicrm_api3($update_entity, $update_action, $update_data);
        $processing_event->logProcessingMessage("Updated contact [{$local_contact_id}].");
        $processing_event->setProcessed();
      } catch (\Exception $ex) {
        $processing_event->logProcessingMessage("Creating/Updating contact failed: " . $ex->getMessage());
        $processing_event->setNewChangeStatus(Change::STATUS_ERROR);
      }
    } else {
      // if it's still not found, mark as error
      $processing_event->logProcessingMessage("Local contact not identified, giving up");
      $processing_event->setNewChangeStatus(Change::STATUS_ERROR);
      return;
    }
  }
}
