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
 * Generic CiviShare configuration
 */
class CRM_Share_Configuration {

  /**
   * Is the changed detection via pre/post hook enabled?
   */
  public static function hook_change_detection_enabled() {
    // TODO: make configurable
    return TRUE;
  }


  /**
   * Get the (local) ID of the local CiviShare node
   */
  public static function getLocalNodeID() {
    // TODO: don't hard-code
    return 1;
  }

}