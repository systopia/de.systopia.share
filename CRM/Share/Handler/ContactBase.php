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
    $data_before = $change->getJSONData('data_before');
    $data_after  = $change->getJSONData('data_after');
    if (!isset($data_after) || !isset($data_before)) {
      throw new Exception("No change data!");
    }

    // load contact
    $fields = array_unique(array_merge(array_keys($data_before), array_keys($data_after)));
    $contact_id = $change->getContactID();
    if (empty($contact_id)) {
      throw new Exception("No contact linked to the change!");
    }
    $data_current = civicrm_api3('Contact', 'getsingle', [
        'id'     => $contact_id,
        'return' => implode(',', $fields),
    ]);

    // compile update
    // TODO: for now, only apply if the values haven't changed
    $contact_update = [];
    $allowed_fields = $this->getFieldList();
    foreach ($data_after as $field => $new_value) {
      if (in_array($field, $allowed_fields)) {
        // check if we have a prior value
        if (isset($data_before[$field]) && $this->hasFieldChanged($field, $data_current, $data_before)) {
          $this->log("Won't apply '{$field}', value has changed.", $change, 'debug');
          continue;
        }

        // all good?
        $this->applyUpdate($field, $contact_update, $new_value, $data_current);
      }
    }

    if (empty($contact_update)) {
      $this->log("No applicable changes detected.", $change);
      return FALSE;
    } else {
      $this->log("Will update contact [{$contact_id}]: " . json_encode($contact_update), 'debug');
      $contact_update['id'] = $contact_id;
      civicrm_api3('Contact', 'create', $contact_update);
      return TRUE;
    }
  }

  /**
   * Check if the given field value is different in the two data sets.
   *  This method should encapsulate all special cases in contact base data
   *
   * @param $field string field name
   * @param $data1 array data
   * @param $data2 array data
   * @return boolean TRUE if changed
   */
  protected function hasFieldChanged($field, $data1, $data2) {
    $value1 = $data1[$field];
    $value2 = CRM_Utils_Array::value($field, $data2);
    switch ($field) {
      default:
        return $value1 != $value2;
    }
  }


  /**
   * Add the right value to the contact update data
   *  This method should encapsulate all special cases in contact base data
   *
   * @param $field           string field name
   * @param $contact_update  array the Contact.create API3 data
   * @param $new_value       mixed the new value submitted
   * @param $contact_current array current contact data - if the data is identical, there's nothing to do
   */
  protected function applyUpdate($field, &$contact_update, $new_value, $contact_current) {
    $current_value = CRM_Utils_Array::value($field, $contact_current);
    switch ($field) {
      default:
        if ($current_value != $new_value) {
          $contact_update[$field] = $new_value;
        }
        break;
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
        $this->createLocalChangeRecord($id, $pre_record, $current_data, 'now');
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
