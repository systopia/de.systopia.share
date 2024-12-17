<?php

namespace Civi\Share\ChangeProcessor;

use Civi\Core\Event\GenericHookEvent as Event;
use Civi\Api4\ShareChange;
use Civi\Api4\ShareNode;
use Civi\Share\Change;

class DefaultContactBaseChangeProcessor extends ChangeProcessorBase {

  /**
   * Process the given change if you can/should/want and don't forget to mark
   * processed when done
   *
   * @param $processing_event
   * @param $event_type
   * @param $dispatcher
   *
   * @return void
   */
  public function process_change($processing_event, $event_type, $dispatcher) {
    // nothing to do here
    if ($processing_event->isProcessed()) {
      return;
    }

    // check if this is the one we're looking for
    if (!$processing_event->hasChangeType('civishare.change.contact.base')) {
      return;
    }

    /***************************************/
    /****  CONTACT IDENTIFICATION PHASE  ***/
    /***************************************/
    $remote_contact_id = (int) $processing_event->getRemoteContactID();
    $local_contact_id = $processing_event->getLocalContactID();

    if (!$local_contact_id) { // local contact not known, look for it...
      // IDENTIFY LOCAL CONTACT: using identification attribute sets (later from config)
      $identification_attribute_sets = $this->getConfigValue('contact_identifying_attribute_sets', [
        ['first_name', 'last_name', 'contact_type', 'email'],
        ['first_name', 'contact_type', 'email'],
      ]);

      // now that we have the attribute sets for identification, iterate lookups until we find a match
      foreach ($identification_attribute_sets as $attribute_set) {
        // search query
        $search_parameters = $this->buildSearchParameters($attribute_set, $processing_event->getChangeDataBefore());
        $processing_event->logProcessingMessage("Using attributes " . json_encode($attribute_set) . " to identifiy contact...");
        $result = \civicrm_api3('Contact', 'get', $search_parameters);
        if ($result['count'] == 1) {
          $local_contact_id = $result['id'];
          $processing_event->logProcessingMessage("Local contact {$local_contact_id} identified.");
          break;
        }
        else {
          $processing_event->logProcessingMessage("Local contact not be identified via attributes: " . json_encode($attribute_set));
        }
      }
    }

    /***************************************/
    /****   CONTACT CREATE/UPDATEPHASE   ***/
    /***************************************/
    if ($local_contact_id) {
      // FIRST: store this information as a new peering
      if ($remote_contact_id) {
        $peering = new \Civi\Share\IdentityTrackerContactPeering(); // refactor as service
        $change = $processing_event->getChange();
        $peering->peer($remote_contact_id, $local_contact_id, $change->getSourceNodeId());

        // run a CONTACT UPDATE
        $update_entity = $this->getConfigValue('update_entity', 'Contact');
        $update_action = $this->getConfigValue('update_action', 'create');
        $update_data = $processing_event->getChangeDataAfter();
        $update_data['id'] = $local_contact_id;

        // basically: process the data by applying to the given local contact:
        try {
          $processing_event->logProcessingMessage("Creating/Updating contact: " . json_encode($change->serialize()));
          \civicrm_api3($update_entity, $update_action, $update_data);
          $processing_event->logProcessingMessage("Updated contact [{$local_contact_id}].");
          $processing_event->setProcessed();
        }
        catch (\Exception $ex) {
          $processing_event->logProcessingMessage("Updating contact failed: " . $ex->getMessage());
          $processing_event->setNewChangeStatus(Change::STATUS_ERROR);
        }
      }
    }
    else {
      // CONTACT COULD *NOT* BE IDENTIFIED
      $create_new_contact = $this->getConfigValue('create_new_contact', TRUE);
      if ($create_new_contact) {
        try {
          // CREATE A NEW CONTACT!
          $create_entity = $this->getConfigValue('create_entity', 'Contact');
          $create_action = $this->getConfigValue('create_action', 'create');
          $update_data = $processing_event->getChangeDataAfter();
          $new_contact = \civicrm_api3($create_entity, $create_action, $update_data);
          $local_contact_id = $new_contact['id'];
          $processing_event->logProcessingMessage("Created contact [{$local_contact_id}]");

          // peer the new contact
          if ($remote_contact_id) {
            $change = $processing_event->getChange();
            $peering = new \Civi\Share\IdentityTrackerContactPeering(); // refactor as service
            $peering->peer($remote_contact_id, $local_contact_id, $change->getSourceNodeId());
          }
          // that's it
          $processing_event->setProcessed();
        }
        catch (\Exception $ex) {
          $processing_event->logProcessingMessage("Creating contact failed: " . $ex->getMessage());
          $processing_event->setNewChangeStatus(Change::STATUS_ERROR);
        }
      }
      else {
        // contact NOT identified and creation not enabled
        $processing_event->logProcessingMessage("Couldn't identify contact, and creating a new one is not allowed.");
      }
    }
  }

}
