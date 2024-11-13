<?php
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

use CRM_Share_ExtensionUtil as E;



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
class NodePeeringTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

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

    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp(): void
  {
    // clean tables
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_share_change");
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_share_handler");
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_share_node_peering");
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_share_node");

    parent::setUp();
  }


  /**
   * This will manually set up a peering ON THE SAME SYSTEM
   */
  public function testBasicSetup():void {
    // create a local node
    $local_node = \Civi\Api4\ShareNode::create(false)
      ->addValue('name', 'Local Node 1')
      ->addValue('short_name', 'basic_01')
      ->addValue('is_local', true)
      ->addValue('description', "automated test node")
      ->addValue('rest_url', 'TODO')
      ->addValue('api_key', 'TODO')
      ->addValue('auth_key', 'TODO')
      ->addValue('is_enabled', true)
      ->addValue('receive_identifiers', CRM_Utils_Array::implodePadded(''))
      ->addValue('send_identifiers', '')
      ->execute()
      ->first();


    // create a "remote" node
    $remote_node = \Civi\Api4\ShareNode::create(false)
      ->addValue('name', 'basic_02_remote')
      ->addValue('short_name', 'Basic "Remote" Node')
      ->addValue('is_local', false)
      ->execute()
      ->first();

    // create a peering (cheekily
    $shared_key = base64_encode(random_bytes(64));
    $node_peering = \Civi\Api4\ShareNodePeering::create(TRUE)
      ->addValue('local_node', $local_node['id'])
      ->addValue('remote_node', $remote_node['id'])
      ->addValue('is_enabled', true)
      ->addValue('shared_secret', $shared_key)
      ->execute();


    $this->assertTrue(true);

  }

  /**
   * This will manually set up a peering ON THE SAME SYSTEM
   */
  public function testPeeringSetup():void {
    // create a local node
    $this->assertTrue(true);
  }

}
