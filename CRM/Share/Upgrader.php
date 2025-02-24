<?php
use CRM_Share_ExtensionUtil as E;
/**
 * Collection of upgrade steps.
 */
class CRM_Share_Upgrader extends \CRM_Extension_Upgrader_Base {

  public function upgrade_0003(): bool {
    $this->ctx->log->info('Adding and renamimg columns for ShareChange entity.');
    self::changeColumn(
      'civicrm_share_change',
      'triggerd_by',
      'triggered_by',
      'text'
    );
    self::addColumn(
      'civicrm_share_change',
      'entity_type',
      'varchar(255) COMMENT "The entity type of the change" AFTER triggered_by'
    );
    // TODO: Fill existing change records' entity_type with "Contact".
    self::addColumn(
      'civicrm_share_change',
      'status_message',
      'text COMMENT "Additional information for status" AFTER status'
    );
    self::addColumn(
      'civicrm_share_change',
      'context',
      'text COMMENT "Context data for this change" AFTER data_after'
    );
    return TRUE;
  }

  /**
   * Add a column to a table if it doesn't already exist
   *
   * @param string $table
   * @param string $column
   * @param string $properties
   *
   * @return bool
   */
  public static function changeColumn($table, $column, $newName, $dataType) {
    if (CRM_Core_BAO_SchemaHandler::checkIfFieldExists($table, $column, FALSE)) {
      $query = "ALTER TABLE `$table` CHANGE COLUMN `$column` $newName $dataType";
      CRM_Core_DAO::executeQuery($query, [], TRUE, NULL, FALSE, FALSE);
    }
    return TRUE;
  }

}
