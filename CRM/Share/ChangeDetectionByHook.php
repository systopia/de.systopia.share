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
 * This class handles the detection of changes by hook
 *
 * @deprecated will be handled differently, so this is no longer needed
 */
class CRM_Share_ChangeDetectionByHook {


  protected static $change_stack = [];
  protected static $enabled = TRUE;

  /**
   * Disable change detection
   */
  public static function disable() {
    self::$enabled = FALSE;
  }

  /**
   * Disable change detection
   */
  public static function enable() {
    self::$enabled = TRUE;
  }

  /**
   * Process CiviCRM pre hook
   * @param $op          string operator
   * @param $objectName  string object name
   * @param $id          int    object ID
   * @param $params      array  change parameters
   */
  public static function processPre($op, $objectName, $id, $params) {
    if (self::$enabled) {
      $handlers = CRM_Share_Controller::singleton()->getHandlers();
      foreach ($handlers as $handler) {
        $record = $handler->createPreHookRecord($op, $objectName, $id, $params);
        array_push(self::$change_stack, $record);
      }
    }
  }

  /**
   * Process CiviCRM post hook
   * @param $op          string operator
   * @param $objectName  string object name
   * @param $id          int    object ID
   * @param $objectRef   mixed  depends on the hook (afaik)
   */
  public static function processPost($op, $objectName, $id, $objectRef) {
    if (self::$enabled) {
      $handlers = CRM_Share_Controller::singleton()->getHandlers();
      $handlers = array_reverse($handlers); // process is reverse order (this is a stack)
      foreach ($handlers as $handler) {
        $pre_record = array_pop(self::$change_stack);
        $handler->createPostHookChange($pre_record, $op, $objectName, $id,  $objectRef);
      }
    }
  }
}
