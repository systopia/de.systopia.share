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
 * Change Record Interface
 *
 * @todo turn into CiviCRM entity
 */
class CRM_Share_Change {

  protected $change_id   = NULL;
  protected $change_data = NULL;

  public function __construct($id) {
    $this->change_id   = (int) $id;
    $this->change_data = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_share_change WHERE id = {$this->change_id};");
    if (!$this->change_data->fetch()) {
      throw Exception("Change [{$this->change_id}] not found.");
    }
  }

  /**
   * Create/insert a new change entry
   *
   * @param $change_id        string unique change ID
   * @param $handler_class    string handler class
   * @param $source_node_id   int source node ID (in civicrm_share_node)
   * @param $data_before      array data/object
   * @param $data_after       array data/object
   * @param $change_date      string date/timestamp
   *
   * @return CRM_Share_Change the newly created change
   */
  public static function createNewChangeRecord($change_id, $handler_class, $source_node_id, $data_before, $data_after, $change_date) {
    $data_before_string = json_encode($data_before);
    $data_after_string  = json_encode($data_after);
    $change_hash        = sha1($data_before_string . $data_after_string);
    $change_date_string = date('YmdHis', strtotime($change_date));
    $status             = $source_node_id == CRM_Share_Configuration::getLocalNodeID() ? 'LOCAL' : 'PENDING';

    CRM_Core_DAO::executeQuery("
    INSERT INTO civicrm_share_change (change_id, hash, handler_class, source_node_id, data_before, data_after, change_date, received_date, status)
                               VALUES(%1, %2, %3, %4, %5, %6, %7, NOW(), %8);", [
                                   1 => [$change_id, 'String'],
                                   2 => [$change_hash, 'String'],
                                   3 => [$handler_class, 'String'],
                                   4 => [$source_node_id, 'Integer'],
                                   5 => [$data_before_string, 'String'],
                                   6 => [$data_after_string, 'String'],
                                   7 => [$change_date_string, 'String'],
                                   8 => [$status, 'String']]);

    $change_id = CRM_CORE_DAO::singleValueQuery('SELECT LAST_INSERT_ID()');
    return new CRM_Share_Change($change_id);
  }
}