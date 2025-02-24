<?php

declare(strict_types = 1);

namespace Civi\Share\EventSubscriber;

use Civi\Api4\Contact;
use Civi\Api4\ShareChange;
use Civi\Api4\ShareNode;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoSubscriber;
use Civi\Share\Utils;

class ContactChangeSubscriber extends AutoSubscriber {

  /**
   * Entities referencing contacts to monitor for changes.
   */
  public const ENTITIES = [
    'Email',
    'Address',
    'Phone',
    'Website',
  ];

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_pre' => 'preEntity',
      'hook_civicrm_post' => 'postEntity',
    ];
  }

  public function preEntity(GenericHookEvent $event): void {
    /**
     * @var string $op
     * @var string $objectName
     * @var int|null $id
     * @var array $params
     */
    [$op, $objectName, $id, $params] = $event->getHookValues();
    // TODO: Handle other operations.
    if ('edit' === $op) {
      if (!\Civi\Api4\Utils\CoreUtil::isContact($event->entity) && !in_array($objectName, self::ENTITIES)) {
        return;
      }

      $entity = \Civi\Api4\Utils\CoreUtil::isContact($event->entity) ? 'Contact' : $objectName;
      $fields = \civicrm_api4($entity, 'getFields', ['select' => ['name']])
        ->column('name');
      $submittedFields = array_intersect_key($params, array_flip($fields));

      $currentEntity = \civicrm_api4(
        $entity,
        'get',
        [
          'select' => array_keys($submittedFields),
          'where' => [['id', '=', $id]],
        ]
      )
        ->single();

      foreach ($submittedFields as $fieldName => $submittedValue) {
        if ($submittedValue !== $currentEntity[$fieldName]) {
          \Civi::$statics[__CLASS__]['changes'][$entity][$id]['before'][$fieldName] = $currentEntity[$fieldName] ?? NULL;
        }
      }
    }
  }

  public function postEntity(GenericHookEvent $event): void {
    /**
     * @var string $op
     * @var string $objectName
     * @var int $objectId
     * @var \CRM_Contact_BAO_Contact $objectRef
     */
    [$op, $objectName, $objectId, $objectRef] = $event->getHookValues();
    // TODO: Handle other operations.
    if (!\Civi\Api4\Utils\CoreUtil::isContact($event->entity) && !in_array($objectName, self::ENTITIES)) {
      return;
    }

    $entity = \Civi\Api4\Utils\CoreUtil::isContact($event->entity) ? 'Contact' : $objectName;

    switch ($op) {
      case 'edit':
        $dataBefore = \Civi::$statics[__CLASS__]['changes'][$entity][$objectId]['before'] ?? [];
        $changedFields = array_keys($dataBefore);
      // Intentional fall-through to "create" without break.

      case 'create':
        // Load created/updated contact.
        $changedFields ??= [];
        $selectFields = [] !== $changedFields ? $changedFields : ['*'];
        if ('Contact' !== $entity && !in_array('contact_id', $selectFields)) {
          $selectFields[] = 'contact_id';
        }
        $dataAfter = \civicrm_api4(
          $entity,
          'get',
          [
            'select' => $selectFields,
            'where' => [['id', '=', $objectId]],
          ]
        )
          ->single();
        $contactId = 'Contact' === $entity ? $objectId : $dataAfter['contact_id'];

        if (!isset($dataBefore)) {
          $dataAfter = array_filter($dataAfter, function ($value, $key) {
            // TODO: Filter more irrelevant fields with a value?
            return NULL !== $value
              && '' !== $value
              && [] !== $value
              && 'created_date' !== $key
              && 'modified_date' !== $key
              && 'hash' !== $key
              && 'id' !== $key;
          }, ARRAY_FILTER_USE_BOTH);
          // For "create", assume no values for all fields.
          $dataBefore = array_fill_keys(array_keys($dataAfter), NULL);
        }
        else {
          // Remove fields with identical values
          // (occurs when comparison in pre hook yielded differences that weren't persisted or only in type).
          $dataBefore = array_filter($dataBefore, function ($value, $key) use ($dataAfter) {
            return $value !== $dataAfter[$key];
          }, ARRAY_FILTER_USE_BOTH);

          // Remove fields that weren't changed (e.g. the "id" field is always being added by the API).
          $dataAfter = array_intersect_key($dataAfter, $dataBefore);
        }

        if ([] !== $dataBefore && [] !== $dataAfter) {
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
            ->addValue('local_contact_id', $contactId)
            ->addValue('source_node_id', $localNodeId)
            ->addValue('entity_type', $entity)
            ->addValue('data_before', $dataBefore)
            ->addValue('data_after', $dataAfter)
            ->execute();

          if (isset(\Civi::$statics[__CLASS__]['changes'][$entity][$objectId])) {
            unset(\Civi::$statics[__CLASS__]['changes'][$entity][$objectId]);
          }
        }
        break;
    }
  }

}
