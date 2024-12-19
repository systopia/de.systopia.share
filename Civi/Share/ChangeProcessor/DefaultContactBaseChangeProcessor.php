<?php

namespace Civi\Share\ChangeProcessor;

use Civi\Core\Event\GenericHookEvent as Event;
use Civi\Api4\ShareChange;
use Civi\Api4\ShareNode;
use Civi\Share\Change;
use Civi\Share\IdentityTrackerContactPeering;

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
    $remote_contact_id = $processing_event->getRemoteContactID();
    $local_contact_id = $processing_event->getLocalContactID();
    $change = $processing_event->getChange();

    // TODO: Replace identification with XCM implementation (or make it optional).
    if (NULL === $local_contact_id) {
      // local contact not known, look for it...
      // IDENTIFY LOCAL CONTACT: using identification attribute sets (later from config)
      $identification_attribute_sets = $this->getConfigValue('contact_identifying_attribute_sets', [
        ['first_name', 'last_name', 'contact_type', 'email'],
        ['first_name', 'contact_type', 'email'],
      ]);

      // now that we have the attribute sets for identification, iterate lookups until we find a match
      foreach ($identification_attribute_sets as $attribute_set) {
        // Search query: use data before the change, but fall back to data after the change if no attribute was matched.
        // This will most likely be a new record, which has no before values.
        // TODO: The final semantics of this behavior will have to be defined.
        $search_parameters = $this->buildSearchParameters(
          $attribute_set,
          $processing_event->getChangeDataBefore(),
          $processing_event->getChangeDataAfter()
        );
        $processing_event->logProcessingMessage("Using attributes " . json_encode($attribute_set) . " to identifiy contact...");
        $result = \civicrm_api3('Contact', 'get', $search_parameters);
        if ($result['count'] == 1) {
          $local_contact_id = $result['id'];
          // Store this information as a new peering.
          if (NULL !== $remote_contact_id) {
            $peering = new \Civi\Share\IdentityTrackerContactPeering(); // refactor as service
            $peering->peer($remote_contact_id, $local_contact_id, $change->getSourceNodeId());
          }
          $processing_event->logProcessingMessage("Local contact {$local_contact_id} identified.");
          break;
        }
        else {
          $processing_event->logProcessingMessage("Local contact could not be identified via attributes: " . json_encode($attribute_set));
        }
      }
    }

    /***************************************/
    /****   CONTACT CREATE/UPDATEPHASE   ***/
    /***************************************/
    // TODO: Make using XCM configurable
    if (\CRM_Extension_Manager::STATUS_INSTALLED === \CRM_Extension_System::singleton()
        ->getManager()
        ->getStatus('de.systopia.xcm')) {
      $defaultCreateAction = $defaultUpdateAction = 'createifnotexists';
      $xcmProfile = $this->getConfigValue('xcm_profile', 'default');
    }
    else {
      $defaultCreateAction = $defaultUpdateAction = 'create';
    }

    /**
     * @var string $create_entity
     * @var string $create_action
     * @var string $update_entity
     * @var string $update_action
     * @var bool $create_new_contact
     * @var string $xcmProfile
     */
    $create_entity = $this->getConfigValue('create_entity', 'Contact');
    $create_action = $this->getConfigValue('create_action', $defaultCreateAction);
    $update_entity = $this->getConfigValue('update_entity', 'Contact');
    $update_action = $this->getConfigValue('update_action', $defaultUpdateAction);
    $create_new_contact = $this->getConfigValue('create_new_contact', TRUE);
    $update_data = $processing_event->getChangeDataAfter();

    // Use XCM if configured.
    if (
      'Contact' === $create_entity && 'createifnotexists' === $create_action
      && 'Contact' === $update_entity && 'createifnotexists' === $update_action
    ) {
      $update_data['xcm_profile'] = $xcmProfile;
      $update_data['match_only'] = !$create_new_contact;

      if (NULL !== $local_contact_id) {
        $update_data['id'] = $local_contact_id;
      }

      try {
        $processing_event->logProcessingMessage("Creating/Updating contact using XCM: " . json_encode($change->serialize()));
        $xcmResult = \civicrm_api3('Contact', 'createifnotexists', $update_data);

        if ((bool) $xcmResult['was_created']) {
          $processing_event->logProcessingMessage("Created contact [{$xcmResult['contact_id']}] using XCM.");
        }
        else {
          $processing_event->logProcessingMessage("Updated contact [{$xcmResult['contact_id']}] using XCM.");
        }

        // Peer contact if not yet peered.
        if (
          NULL === $local_contact_id
          && NULL !== $remote_contact_id
        ) {
          $change = $processing_event->getChange();
          $peering = new IdentityTrackerContactPeering();
          $peering->peer($remote_contact_id, $xcmResult['contact_id'], $change->getSourceNodeId());
        }

        $processing_event->setProcessed();
      }
      catch (\Exception $ex) {
        $processing_event->logProcessingMessage("Creating/Updating contact using XCM failed: " . $ex->getMessage());
        $processing_event->setNewChangeStatus(Change::STATUS_ERROR);
      }
    }

    // Use standard API.
    else {
      if ($local_contact_id) {
        // run a CONTACT UPDATE
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
      else {
        // CONTACT COULD *NOT* BE IDENTIFIED
        $create_new_contact = $this->getConfigValue('create_new_contact', TRUE);
        if ($create_new_contact) {
          try {
            // CREATE A NEW CONTACT!
            $new_contact = \civicrm_api3($create_entity, $create_action, $update_data);
            $local_contact_id = $new_contact['id'];
            $processing_event->logProcessingMessage("Created contact [{$local_contact_id}]");

            // peer the new contact
            if (NULL !== $remote_contact_id) {
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

}
