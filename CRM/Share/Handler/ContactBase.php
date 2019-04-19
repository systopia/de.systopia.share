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
 */
class CRM_Share_Handler_ContactBase extends CRM_Share_Handler {

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
    if ($objectName == 'Individual' || $objectName == 'Household' || $objectName == 'Organization') {
      if ($op == 'create' || $op == 'edit') {
        if (empty($id)) {
          // a new contact is created => we don't want to detect this.
        } else {
          if ($this->isContactCurrentlyLinked($id)) {
            return civicrm_api3('Contact', 'getsingle', [
                'id'     => $id,
                'return' => 'id,' . $this->getFieldList(TRUE)
            ]);
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
      $current_data = civicrm_api3('Contact', 'getsingle', [
          'id'     => $id,
          'return' => 'id,' . $this->getFieldList(TRUE)
      ]);
      $diff = $this->dataDiff($pre_record, $current_data);
      if (!empty($diff)) {
        $this->createLocalChangeRecord($pre_record, $current_data, 'now');
      }
    }
  }

  /**
   * Get the list of fields this handler needs
   *
   * @param bool $as_string  should the fields be returned as a cs string instead of an array
   *
   * @return string|array list of fields
   */
  protected function getFieldList($as_string = FALSE) {
    // TODO: make configurable
    $fields = ['first_name', 'last_name', 'formal_title', 'prefix_id', 'organization_name', 'birth_date'];
    if ($as_string) {
      return implode(',', $fields);
    } else {
      return $fields;
    }
  }

}
