<?php

declare(strict_types = 1);

namespace Civi\Share\ChangeProcessor;

use Civi\Share\Change;
use Civi\Share\ChangeProcessingEvent;
use Civi\Share\IdentityTrackerContactPeering;

class DefaultContactBaseChangeProcessor extends AbstractChangeProcessor {

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

    if (\CRM_Extension_Manager::STATUS_INSTALLED !== \CRM_Extension_System::singleton()
        ->getManager()
        ->getStatus('de.systopia.xcm')) {
      $this->log(
        $processing_event->getNodeId(),
        $processing_event->getChange()->getId(),
        'Extension "de.systopia.xcm" missing for processing change.'
      );
      return;
    }

    /***************************************/
    /****  CONTACT IDENTIFICATION PHASE  ***/
    /***************************************/
    $remote_contact_id = $processing_event->getRemoteContactID();
    $local_contact_id = $processing_event->getLocalContactID();
    $change = $processing_event->getChange();

    // TODO: Replace identification with XCM implementation (or make it optional).
    $local_contact_id ??= $this->identifyContact($processing_event);

    /***************************************/
    /****   CONTACT CREATE/UPDATEPHASE   ***/
    /***************************************/
    $create_new_contact = TRUE;
    $update_data = $processing_event->getChangeDataAfter();
    $update_data = $this->getUpdateDataForEntity(
      $processing_event->getChangeDataAfter(),
      $processing_event->getChange()->getEntityIdentificationContext(),
      $processing_event->getChange()->getEntityType()
    );
    $update_data['xcm_profile'] = $this->getConfigValue('xcm_profile', 'default');
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

  protected function identifyContact(ChangeProcessingEvent $processing_event) {
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

    return $local_contact_id ?? NULL;
  }

  protected function getUpdateDataForEntity(array $data, array $entityIdentificationContext = [], string $entity = 'Contact'): array {
    $updateData = [];
    // TODO: Now that changes of the type "civishare.change.contact.base" can be of different entity types (Address,
    //       Email, etc.), prepare $update_data for XCM.

    // TODO: Add attributes for identifying the contact/entity (e. g. location type).

    if ('Website' === $entityType) {
      $updateData['website'] = $data['url'];
    }

    if ('Phone' === $entityType) {
      // TODO: Compare location type for "phone", phone2", "phone3" configured in XCM profile with submitted location
      //       type.
    }

    if ('Address' === $entityType) {
      $updateData += $data;
      // TODO: Add location type from context.
    }

    return $updateData;
  }

}
