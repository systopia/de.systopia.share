<?php

declare(strict_types = 1);

use Civi\Api4\ShareChange;
use Civi\Api4\ShareNode;
use Civi\Api4\ShareNodePeering;
use Civi\Test;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use CRM_Share_ExtensionUtil as E;
use PHPUnit\Framework\TestCase;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class NodePeeringTest extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  /**
   * Setup used when HeadlessInterface is implemented.
   *
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   *
   * @link https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   *
   * @return \Civi\Test\CiviEnvBuilder
   *
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function setUpHeadless() {
    //
    //
    //    // delete tables
    //    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS civicrm_share_change");
    //    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS  civicrm_share_handler");
    //    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS  civicrm_share_node_peering");
    //    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS  civicrm_share_node");

    $result = Test::headless()
      ->installMe(__DIR__)
      ->apply();
    return $result;
  }

  public function setUp(): void {
    // clean tables
    parent::setUp();
    \CRM_Core_DAO_AllCoreTables::flush();

    parent::setUp();
  }

  /**
   * This will manually set up a peering ON THE SAME SYSTEM
   *
   * @coversNothing
   */
  public function testBasicSetup():void {

    \CRM_Core_DAO_AllCoreTables::flush();
    // create a local node
    $local_node = ShareNode::create(FALSE)
      ->addValue('name', 'Local Node 1')
      ->addValue('short_name', 'basic_01')
      ->addValue('is_local', TRUE)
      ->addValue('description', 'automated test node')
      ->addValue('rest_url', 'TODO')
      ->addValue('api_key', 'TODO')
      ->addValue('auth_key', 'TODO')
      ->addValue('is_enabled', TRUE)
      ->addValue('receive_identifiers', CRM_Utils_Array::implodePadded(''))
      ->addValue('send_identifiers', '')
      ->execute()
      ->first();

    // create a "remote" node
    $remote_node = ShareNode::create(FALSE)
      ->addValue('name', 'basic_02_remote')
      ->addValue('short_name', 'Basic "Remote" Node')
      ->addValue('is_local', FALSE)
      ->execute()
      ->first();

    // create a peering (cheekily)
    $shared_key = base64_encode(random_bytes(64));
    $node_peering = ShareNodePeering::create(TRUE)
      ->addValue('local_node', $local_node['id'])
      ->addValue('remote_node', $remote_node['id'])
      ->addValue('is_enabled', TRUE)
      ->addValue('shared_secret', $shared_key)
      ->execute();

    // create a change
    $change = ShareChange::create(TRUE)
      ->addValue('change_type', 'civishare.change.contact.base')
      ->addValue('source_node_id', $remote_node['id'])
      ->addValue('change_date', date('Y-m-d H:i:s'))
      ->addValue('status', 'LOCAL')
      ->execute();

    $this->assertTrue(TRUE);

  }

  /**
   * This will manually set up a peering ON THE SAME SYSTEM
   *
   * @coversNothing
   */
  public function testPeeringSetup():void {
    // create a local node
    $this->assertTrue(TRUE);
  }

}
