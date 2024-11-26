<?php
/*-------------------------------------------------------+
| CiviShare                                              |
| Copyright (C) 2024 SYSTOPIA                            |
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


class CRM_Share_TestTools
{
  public static function clearCiviShareConfig()
  {
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_share_handler");
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_share_change");
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_share_node_peering");
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_share_node");
  }
}
