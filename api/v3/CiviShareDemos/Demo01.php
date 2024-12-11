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

use \Civi\Share\Message;
use \Civi\Share\ChangeProcessingEvent;

/**
 * Demo01 for CiviShare
 *
 * - find the local node
 * - find remote node(s)
 * - generate a change
 * - send it
 *
 * @todo migrate to unit tests (once running)
 **/
function civicrm_api3_civi_share_demos_demo01(&$params) {
  // make sure there is one local node
  if (!empty($params['local_node_id'])) {
    $local_node_id = (int) $params['local_node_id'];
  }

  // todo: enable change tracker

  // todo: generate random change

  // register ShareHandler


  return civicrm_api3_create_success();
}


/**
 * Process test events
 *
 * @param \Civi\Share\ChangeProcessingEvent $processing_event
 * @param string $event_type
 * @param $dispatcher
 * @return void
 */
function civicrm_civi_share_test_register_test_hander($processing_event, $event_type, $dispatcher)
{
  // nothing to do here
  if ($processing_event->isProcessed()) return;

  // check if this is the one we're looking for
  if ($processing_event->hasChangeType('civishare.change.test')) {
    $processing_event->setProcessed();
  }
}

