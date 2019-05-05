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
 * This is the command and control centre of CiviShare
 */
class CRM_Share_Controller {

  private static $singleton = NULL;

  protected $handlers = NULL;
  protected $contact_link_status = [];

  /**
   * Get the CiviShare controller instance
   *
   * @return CRM_Share_Controller|null
   */
  public static function singleton() {
    if (self::$singleton === NULL) {
      self::$singleton = new CRM_Share_Controller();
    }
    return self::$singleton;
  }

  /**
   * Get a lock to prevent other tasks (on this system) to work on
   *  the changes structure
   *
   * @param $timeout int timeout in seconds
   * @return mixed lock
   * @throws Exception should there be a timeout
   */
  public function getChangesLock($timeout = 30) {
    // TODO: implement
    return TRUE;
  }

  /**
   * Release the previously acquired lock
   *
   * @param $lock mixed lock
   */
  public function releaseLock($lock) {
    // TODO: implement
  }


  /**
   * Provides a (cached) lookup to see if the contact is linked,
   *  and the link is enabled
   *
   * @param $contact_id int contact ID
   * @return 1 if the contact is currently linked, 0 if not
   */
  public function isContactCurrentlyLinked($contact_id) {
    $contact_id = (int) $contact_id;
    if (!isset($this->contact_link_status[$contact_id])) {
      $is_peered = CRM_Core_DAO::singleValueQuery("
        SELECT id 
        FROM civicrm_value_share_link 
        WHERE entity_id = {$contact_id}
          AND is_enabled = 1
        LIMIT 1");
      if ($is_peered) {
        $this->contact_link_status[$contact_id] = 1;
      } else {
        $this->contact_link_status[$contact_id] = 0;
      }
    }
    return $this->contact_link_status[$contact_id];
  }

  /**
   * Notification event: a new change has been created and stored in the DB
   *
   * This can be used to trigger immediate sending...
   *
   * @param $change_id
   * @todo use Symfony events
   */
  public function newChangeStored($change_id) {
    // TODO: anything?
  }

  /**
   * Log a message
   *
   * @param $message  string the message
   * @param $level    string log level (debug, info, warn, error)
   */
  public function log($message, $level = 'info') {
    // TODO: implement log levels
    CRM_Core_Error::debug_log_message("CiviShare: " . $message);
  }

  /**
   * Get the list of active handlers
   *
   * @return null
   */
  public function getHandlers()
  {
    if ($this->handlers === NULL) {
      $this->handlers = [];
      // run the query first
      $query = CRM_Core_DAO::executeQuery("
        SELECT 
          id            AS handler_id, 
          name          AS handler_name, 
          class         AS handler_class, 
          configuration AS handler_configuration
        FROM civicrm_share_handler
        WHERE is_enabled = 1
        ORDER BY weight ASC;");
      while ($query->fetch()) {
        if (class_exists($query->handler_class)) {
          $configuration = json_decode($query->handler_configuration, TRUE);
          if ($configuration === NULL) {
            $this->log("Handler [{$query->handler_class}] has an invalid configuration.", 'error');
            $configuration = [];
          }
          $this->handlers[] = new $query->handler_class($query->handler_id, $query->handler_name, $configuration);
        } else {
          $this->log("Unknown handler class {$query->handler_class}, handler skipped.", 'error');
        }
      }
    }
    return $this->handlers;
  }

  /**
   * Get a unique host ID
   */
  public function HostID() {
    // TODO: there has to be a better way than using the BASE URL
    $host_id = substr(CIVICRM_UF_BASEURL, 5);
    $host_id = trim($host_id, ' /:?&');
    return $host_id;
  }

  /**
   * Generate a new change ID for this system
   */
  public function generateChangeID() {
    // TODO: Lock needed?
    $dsn = DB::parseDSN(CIVICRM_DSN);
    $last_id = (int) CRM_Core_DAO::singleValueQuery("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$dsn['database']}'  AND TABLE_NAME = 'civicrm_share_change'");
    $host_id = $this->HostID();
    return "{$host_id}::{$last_id}";
  }

  /**
   * Call an external system using the REST API
   *
   * @todo  alternative implementations
   *
   * @param $entity   string the entity
   * @param $action   string the action
   * @param $params   array  parameters
   * @param $rest_url string the REST API endpoint
   * @param $site_key string site key
   * @param $api_key  string api key
   *
   * @return array result
   */
  public function restAPI3($entity, $action, $params, $rest_url, $site_key, $api_key) {
    // TODO: this is a simple CURL based implementation. We might want some abstraction here

    // extract site key
    $params['key']        = $site_key;
    $params['api_key']    = $api_key;
    $params['json']       = 1;
    $params['version']    = 3;
    $params['entity']     = $entity;
    $params['action']     = $action;

    $curlSession = curl_init();
    curl_setopt($curlSession, CURLOPT_POST,           1);
    curl_setopt($curlSession, CURLOPT_POSTFIELDS,     $params);
    curl_setopt($curlSession, CURLOPT_URL,            $rest_url);
    curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, 1);
    if (!empty($target_interface)) {
      curl_setopt($curlSession, CURLOPT_INTERFACE, $target_interface);
    }
    // curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, 2);

    $response = curl_exec($curlSession);

    if (curl_error($curlSession)){
      return [
          'is_error'  => 1,
          'error_msg' => curl_error($curlSession)];
    } else {
      return json_decode($response, true);
    }
  }
}