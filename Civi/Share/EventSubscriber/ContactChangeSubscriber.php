<?php

declare(strict_types = 1);

namespace Civi\Share\EventSubscriber;

use api\v4\Custom\FalseNotEqualsZeroTest;
use Civi\Api4\Contact;
use Civi\Api4\ShareChange;
use Civi\Api4\ShareNode;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoSubscriber;
use Civi\Share\Utils;

class ContactChangeSubscriber extends AutoSubscriber {

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_pre' => 'preContact',
      'hook_civicrm_post' => 'postContact',
    ];
  }

  public function preContact(GenericHookEvent $event): void {
    /**
     * @var string $op
     * @var string $objectName
     * @var int|null $id
     * @var array $params
     */
    [$op, $objectName, $id, &$params] = $event->getHookValues();
    // TODO: Handle other operations.
    if ('edit' === $op && \Civi\Api4\Utils\CoreUtil::isContact($event->entity)) {
      // TODO: This does not include "custom_" fields submitted in $params - and is generally not comparing compatible
      //       data structures. Which keys can $params contain in the first place?
      $fields = Contact::getFields(FALSE)
        ->addSelect('name')
        ->execute()
        ->column('name');
      $submittedFields = array_intersect_key($params, array_flip($fields));

      $currentContact = Contact::get(FALSE)
        ->addSelect(...array_keys($submittedFields))
        ->addWhere('id', '=', $id)
        ->execute()
        ->single();

      foreach (array_diff($submittedFields, $currentContact) as $fieldName => $change) {
        \Civi::$statics[__CLASS__]['changes'][$id]['before'][$fieldName] = $currentContact[$fieldName] ?? NULL;
      }
    }
  }

  public function postContact(GenericHookEvent $event): void {
    /**
     * @var string $op
     * @var string $objectName
     * @var int $objectId
     * @var \CRM_Contact_BAO_Contact $objectRef
     */
    [$op, $objectName, $objectId, &$objectRef] = $event->getHookValues();
    // TODO: Handle other operations.
    if ('edit' === $op && \Civi\Api4\Utils\CoreUtil::isContact($event->entity)) {
      $dataBefore = \Civi::$statics[__CLASS__]['changes'][$objectId]['before'] ?? [];
      if ([] !== $dataBefore) {
        $dataAfter = Contact::get(FALSE)
          ->addSelect(...array_keys(\Civi::$statics[__CLASS__]['changes'][$objectId]['before']))
          ->addWhere('id', '=', $objectId)
          ->execute()
          ->single();

        // Remove fields with identical values
        // (occurs when comparison in pre hook yielded differences that weren't persisted or only in type).
        $dataBefore = array_filter($dataBefore, function ($value, $key) use ($dataAfter) {
          return $value !== $dataAfter[$key];
        }, ARRAY_FILTER_USE_BOTH);
        // Remove fields that weren't changed (e.g. the "id" field is always being added by the API).
        $dataAfter = array_intersect_key($dataAfter, $dataBefore);

        if ([] !== $dataBefore) {
          // TODO: How to determine the default local node?
          $localNodeId = ShareNode::get(FALSE)
            ->addSelect('id')
            ->addWhere('is_local', '=', TRUE)
            ->addWhere('is_enabled', '=', TRUE)
            ->execute()
            ->first()['id'];

          $change = ShareChange::create()
            ->addValue('change_type', 'civishare.change.contact.base')
            ->addValue('change_date', (new \DateTime())->format(Utils::CIVICRM_DATE_FORMAT))
            ->addValue('status', \Civi\Share\Change::STATUS_LOCAL)
            ->addValue('local_contact_id', $objectId)
            ->addValue('source_node_id', $localNodeId)
            ->addValue('data_before', $dataBefore)
            ->addValue('data_after', $dataAfter)
            ->execute();
        }
      }

      if (isset(\Civi::$statics[__CLASS__]['changes'][$objectId])) {
        unset(\Civi::$statics[__CLASS__]['changes'][$objectId]);
      }
    }
  }

}
