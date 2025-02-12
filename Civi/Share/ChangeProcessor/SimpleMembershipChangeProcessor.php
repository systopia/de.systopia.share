<?php

declare(strict_types = 1);

namespace Civi\Share\ChangeProcessor;


use Civi\Share\Change;

/**
 *  This simple processor will process membership data, i.e. update an existing
 * membership or create a new one. It works under the assumption that there is
 * at most one active membership per contact, so no further identification of
 * the specific membership is needed
 */
class SimpleMembershipChangeProcessor extends AbstractChangeProcessor {

  public const CHANGE_TYPE_MEMBERSHIP   = 'civishare.change.membership';

  /**
   * Processes membership submissions
   *
   * @param \Civi\Share\ChangeProcessingEvent $processing_event
   * @param string $event_type
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
    if (!$processing_event->hasChangeType(self::CHANGE_TYPE_MEMBERSHIP)) {
      return;
    }

    // get the context to identify the membership
    $entity_context = $processing_event->getEntityIdentificationContext(['contact', 'membership']);





    // do the processing!
    $data_before = $processing_event->getChangeDataBefore();
    $data_after = $processing_event->getChangeDataAfter();
    $remote_contact_id = $processing_event->getContactID();

    // read context data
    $membership = $this->getLocalEntity('membership', $entity_context, $processing_event->getContactID());

    $processing_event->getEntityIdentificationContext()


    $contact_id_context = $this->getContextForEntity('contact');
    $membership_id_context = $this->getContextForEntity('membership');
    $processing_event->getLocalContactID()
    $membership_identifier = $this->getEntityContext('membership', ['contact']);


    /**
     * "entity_identification_context": {
     * "contact.first_name": "Escarlata",
     * "contact.last_name": "La Pirata",
     * "contact.email": "escarlata@pirat.as"
     * },
     * "attri
     */

    // use peering service to find local_contact_id
    // @todo migrate peering to service
    $peering = new \Civi\Share\IdentityTrackerContactPeering();
    $change = $processing_event->getChangeData();
    $changeObject = $processing_event->getChange();
    $change_data = $processing_event->getChangeDataAfter();
    $local_contact_id = $peering->getLocalContactId(
      $remote_contact_id,
      $changeObject->getSourceNodeId(),
      $change['local_node_id']
    );
    if (!$local_contact_id) {
      $processing_event->logProcessingMessage("Couldn't identify contact, processing declined.");
      $processing_event->setNewChangeStatus(Change::STATUS_ERROR);
      return;
    }

    // find membership(s)
    $memberships = \Civi\Api4\Membership::get(TRUE)
      ->addJoin('MembershipStatus AS membership_status', 'LEFT')
      ->addWhere('membership_status.is_active', '=', TRUE)
      ->addWhere('contact_id', '=', $local_contact_id)

      ->setLimit(2)
      ->execute();
    switch (count($memberships)) {
      case 0:
        // no active membership found => create
        $processing_event->logProcessingMessage(
          "No active membership(s) found for contact [{$local_contact_id}]. Creating new membership..."
        );
        try {
          $membership = civicrm_api4('Membership', 'create', $change_data);
          $processing_event->logProcessingMessage("Created new membership [{$membership['id']}].");
          $processing_event->setProcessed();
        }
        catch (\Exception $ex) {
          $error_message = $ex->getMessage();
          // @todo remove sensitive data from log
          $processing_event->logProcessingMessage(
            "Could't create membership ({$error_message}) with data provided: " . json_encode($change_data)
          );
        }
        break;

      case 1:
        // update active membership
        $membership = reset($memberships);
        $processing_event->logProcessingMessage('Updating membership for contact [{$local_contact_id}]');
        try {
          $change_data['id'] = $membership['id'];
          \civicrm_api4('Membership', 'create', $change_data);
          $processing_event->logProcessingMessage("Updated membership [{$membership['id']}].");
          $processing_event->setProcessed();
        }
        catch (\Exception $ex) {
          $error_message = $ex->getMessage();
          // @todo remove sensitive data from log
          $processing_event->logProcessingMessage(
            "Could't create membership ({$error_message}) with data provided: " . json_encode($change_data)
          );
        }
        break;
    }
  }

}
