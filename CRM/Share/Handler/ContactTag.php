<?php
/*-------------------------------------------------------+
| CiviShare                                              |
| Copyright (C) 2019 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*/

/**
 * This is the base class of all CiviShare handlers
 *
 * @deprecated will be handled differently, so this is no longer needed
 */
class CRM_Share_Handler_ContactTag extends CRM_Share_Handler {

  /** @var array $tag_cache caches a tag_id => tag_name list */
  protected static $tag_cache = [];

  /** @var array $tag_name_cache caches a tag_name => tag_id  list */
  protected static $tag_name_cache = [];

  /**
   * Apply the change to the database
   *
   * @param $change CRM_Share_Change the change object to be applied
   *
   * @return boolean TRUE if anything was changed, FALSE if not
   * @throws Exception should there be a problem
   */
  public function apply($change) {
    $this->log("Starting change application...", $change, 'debug');

    // get data
    $data  = $change->getJSONData('data_after');

    // get contact
    $contact_id = $change->getContactID();
    if (empty($contact_id)) {
      throw new Exception("No contact linked to the change!");
    }

    // get tag
    if (empty($data['tag_name'])) {
      throw new Exception("No tag name given!");
    }
    $tag_id = $this->getTagByName($data['tag_name']);

    // check if contact has this tag
    $contact_has_tag = $this->hasTag($contact_id, $tag_id);
    switch ($data['action']) {
      case 'add':
        if ($contact_has_tag) {
          $this->log("Contact [{$contact_id}] already has tag '{$data['tag_name']}'");
          return FALSE;

        } else {
          civicrm_api3('EntityTag', 'create', [
              'entity_id'    => $contact_id,
              'entity_table' => 'civicrm_contact',
              'tag_id'       => $tag_id]);
          $this->log("Contact [{$contact_id}] tagged with '{$data['tag_name']}'");
          return TRUE;
        }
        break;

      case 'remove':
        if ($contact_has_tag) {
          // doesn't work: civicrm_api3('EntityTag', 'delete', ['id' => $contact_has_tag]);
          // this works:
          civicrm_api3('EntityTag', 'delete', [
              'entity_id'    => $contact_id,
              'entity_table' => 'civicrm_contact',
              'tag_id'       => $tag_id]);
          $this->log("Tag '{$data['tag_name']}' has been removed from contact [{$contact_id}].");
          return TRUE;

        } else {
          $this->log("Contact [{$contact_id}] doesn't have tag '{$data['tag_name']}'");
          return FALSE;
        }
        break;

      default:
        throw new Exception("Unknown action '{$data['action']}'.");
    }
  }

  /**
   * If this handler can process pre-hook related change, it can return a record here that
   * will then be passed into createPostHookChange()
   *
   * @param $op          string operator
   * @param $objectName  string object name
   * @param $id          int    object ID
   * @param $params      array  change parameters
   *
   * @return array before_record
   */
  public function createPreHookRecord($op, $objectName, $id, $params) {
    if ($objectName == 'EntityTag') {
      $data = $this->loadTag($id, $params);
      if ($op == 'create' && $data) {
        // tag added
        if ($data['entity_table'] == 'civicrm_contact' && !empty($data['entity_id'])) {
          $contact_id = $data['entity_id'];
          if ($this->isContactCurrentlyLinked($contact_id)) {
            return [
                'contact_id' => $contact_id,
                'tag_id'     => $data['tag_id'],
                'action'     => 'add',
                'tag_name'   => $this->getTagName($data['tag_id']),
            ];
          }
        }
      } elseif ($op == 'delete' && $data) {
        if (empty($data['entity_table']) || empty($data['entity_table']) || empty($data['tag_id'])) {
          // insufficient data => load
          $data = civicrm_api3('EntityTag', 'getsingle', ['id' => $id]);
        }
        if ($data['entity_table'] == 'civicrm_contact' && !empty($data['entity_id'])) {
          $contact_id = $data['entity_id'];
          if ($this->isContactCurrentlyLinked($contact_id)) {
            return [
                'contact_id' => $contact_id,
                'tag_id'     => $data['tag_id'],
                'action'     => 'remove',
                'tag_name'   => $this->getTagName($data['tag_id']),
            ];
          }
        }
      }
    }
    return NULL;
  }

  /**
   * Generate a change record, if a change is detected
   *
   * @param $pre_record  array  previously recorded change
   * @param $op          string operator
   * @param $objectName  string object name
   * @param $id          int    object ID
   * @param $objectRef   mixed  depends on the hook (afaik)
   */
  public function createPostHookChange($pre_record, $op, $objectName, $id,  $objectRef) {
    if (!empty($pre_record)) {
      // TODO: verify that the pre_hook record has actually been created?
      $this->createLocalChangeRecord($pre_record['contact_id'], [], $pre_record, 'now');
    }
  }

  /**
   * @param $tag_id int tag ID
   * @return string tag name
   */
  protected function getTagName($tag_id) {
    $tag_id = (int) $tag_id;
    if ($tag_id) {
      if (!array_key_exists($tag_id, self::$tag_cache)) {
        try {
          $tag_name = civicrm_api3('Tag', 'getvalue', ['id' => $tag_id, 'return' => 'name']);
          self::$tag_cache[$tag_id] = $tag_name;
        } catch(Exception $ex) {
          // probably doesn't exist...
          self::$tag_cache[$tag_id] = NULL;
        }
      }
      return self::$tag_cache[$tag_id];
    } else {
      return NULL;
    }
  }

  /**
   * Get the tag ID by name. If it doesn't exist, it will be created
   *
   * @param $tag_name string name of the tag
   * @return int ID of the tag
   */
  protected function getTagByName($tag_name) {
    if (empty($tag_name)) {
      throw Exception("No tag name given!");
    }

    if (!array_key_exists($tag_name, self::$tag_name_cache)) {
      // find tag
      $tag = civicrm_api3('Tag', 'get', ['name' => $tag_name, 'used_for' => 'civicrm_contact']);
      if (empty($tag['id'])) {
        // not found? create!
        $tag = civicrm_api3('Tag', 'create', [
            'name'          => $tag_name,
            'used_for'      => 'civicrm_contact',
            'is_tagset'     => 0,
            'is_selectable' => 1,
            'is_reserved'   => 1,
            'description'   => 'created by CiviShare'
        ]);
        $this->log("Created missing tag '{$tag_name}'.");
      }
      self::$tag_name_cache[$tag_name] = $tag['id'];
    }
    return self::$tag_name_cache[$tag_name];
  }

  /**
   * Check if the contact has the given tag
   *
   * @param $contact_id int contact ID
   * @param $tag_id     int tag ID
   *
   * @return int|false EntityTag ID
   */
  protected function hasTag($contact_id, $tag_id) {
    $entity_tag = civicrm_api3('EntityTag', 'get', [
        'entity_id'    => $contact_id,
        'tag_id'       => $tag_id,
        'entity_table' => 'civicrm_contact',
        'return'       => 'id'
    ]);
    if (empty($entity_tag['id'])) {
      return FALSE;
    } else {
      return (int) $entity_tag['id'];
    }
  }

  /**
   * Get a sensible data structure about the tag
   *
   * @param $tag_id  int tag ID
   * @param $params  array the other parameters that were passed in the pre hook
   */
  protected function loadTag($tag_id, $params) {
    // remark: idk what's wrong with this call's params...
    return [
        'tag_id'       => $tag_id,
        'entity_table' => $params[1],
        'entity_id'    => $params[0][0]
    ];
  }
}
