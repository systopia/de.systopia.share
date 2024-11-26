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


class CiviShareTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

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

    // drop tables - @todo remove
//    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS civicrm_share_node");
//    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS civicrm_share_node_peering");
//    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS civicrm_share_change");
//    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS civicrm_share_handler");

    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function testMe()
  {
    \CRM_Contribute_BAO_Contribution::fields();
    $this->assertTrue(CRM_Share_DAO_ShareNode::fields());
  }
}
