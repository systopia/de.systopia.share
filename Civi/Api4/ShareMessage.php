<?php
/**
 * ShareMessage API endpoint for delivering ChangeMessage data (transient)
 *
 * Provided by the CiviShare extension.
 *
 * @package Civi\Api4
 */

namespace Civi\Api4;

use Civi\Api4\Generic\AbstractAction;

/**
 * This is the API endpoint to deliver ChangeMessages to.
 *
 * @package Civi\Api4
 */
class ShareMessage extends Generic\AbstractEntity {


  /**
   * Every entity **must** implement `getFields`.
   *
   * This tells the action classes what input/output fields to expect,
   * and also populates the _API Explorer_.
   *
   * The `BasicGetFieldsAction` takes a callback function. We could have defined the function elsewhere
   * and passed a `callable` reference to it, but passing in an anonymous function works too.
   *
   * The callback function takes the `BasicGetFieldsAction` object as a parameter in case we need to access its properties.
   * Especially useful is the `getAction()` method as we may need to adjust the list of fields per action.
   *
   * Note that it's possible to bypass this function if an action class lists its own fields by declaring a `fields()` method.
   *
   * Read more about how to implement your own `GetFields` action:
   * @see \Civi\Api4\Generic\BasicGetFieldsAction
   *
   * @param bool $checkPermissions
   *
   * @return Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\BasicGetFieldsAction(__CLASS__, __FUNCTION__, function($getFieldsAction) {
      return [
        [
          'name' => 'sender',
          'data_type' => 'string',
          'description' => 'sender ID',
        ],
        [
          'name' => 'message_id',
          'data_type' => 'string',
          'description' => 'message ID',
        ],
        [
          'name' => 'data',
          'data_type' => 'string',
          'description' => 'JSON encoded change data',
        ],
        [
          'name' => 'signature',
          'data_type' => 'string',
          'description' => 'signature of the data using the shared secret key',
        ],
        [
          'name' => 'timestamp',
          'data_type' => 'Timestamp',
          'description' => "Time of SENDING of this message. Remark: the individual changes contained in this message have their own timestamp",
        ],
      ];
    }))->setCheckPermissions($checkPermissions);
  }

  /**
   * `BasicGetAction` is the most complex basic action class, but is easy to implement.
   *
   * Simply pass it a function that returns the full array of records (known as the "getter" function),
   * and the API takes care of all the sorting and filtering automatically.
   *
   * Alternately, if performance is a concern and it isn't practical to return all records,
   * your getter can take advantage of some helper functions to optimize for e.g. fetching item(s) by id
   * (the getter receives the `BasicGetAction` object as its argument).
   *
   * Read more about how to implement your own `Get` action:
   * @see \Civi\Api4\Generic\BasicGetAction
   *
   * @param bool $checkPermissions
   *
   * @return Generic\BasicGetAction
   */
  public static function process($checkPermissions = TRUE) {
    return (new AbstractAction(__CLASS__, __FUNCTION__, 'getApi4exampleRecords'))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * `BasicGetAction` is the most complex basic action class, but is easy to implement.
   *
   * Simply pass it a function that returns the full array of records (known as the "getter" function),
   * and the API takes care of all the sorting and filtering automatically.
   *
   * Alternately, if performance is a concern and it isn't practical to return all records,
   * your getter can take advantage of some helper functions to optimize for e.g. fetching item(s) by id
   * (the getter receives the `BasicGetAction` object as its argument).
   *
   * Read more about how to implement your own `Get` action:
   * @see \Civi\Api4\Generic\BasicGetAction
   *
   * @param bool $checkPermissions
   *
   * @return Generic\BasicGetAction
   */
  public static function get($checkPermissions = TRUE) {
    return (new Generic\BasicGetAction(__CLASS__, __FUNCTION__, 'getApi4exampleRecords'))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * `BasicGetAction` is the most complex basic action class, but is easy to implement.
   *
   * Simply pass it a function that returns the full array of records (known as the "getter" function),
   * and the API takes care of all the sorting and filtering automatically.
   *
   * Alternately, if performance is a concern and it isn't practical to return all records,
   * your getter can take advantage of some helper functions to optimize for e.g. fetching item(s) by id
   * (the getter receives the `BasicGetAction` object as its argument).
   *
   * Read more about how to implement your own `Get` action:
   * @see \Civi\Api4\Generic\BasicGetAction
   *
   * @param bool $checkPermissions
   *
   * @return Generic\BasicGetAction
   */
  public static function get($checkPermissions = TRUE) {
    return (new Generic\BasicGetAction(__CLASS__, __FUNCTION__, 'getApi4exampleRecords'))
      ->setCheckPermissions($checkPermissions);
  }
//
//  /**
//   * This demonstrates overriding a basic action class instead of using it directly.
//   *
//   * @param bool $checkPermissions
//   *
//   * @return Action\Example\Create
//   */
//  public static function create($checkPermissions = TRUE) {
//    return (new Action\Example\Create(__CLASS__, __FUNCTION__))
//      ->setCheckPermissions($checkPermissions);
//  }
//
//  /**
//   * `BasicUpdateAction` allows a single record to be updated.
//   *
//   * We pass it a setter function which takes two arguments:
//   *  1. The record to be updated (as an array). Note this only contains an `id` plus fields to be updated,
//   *     not existing data unless the `reload` parameter is set.
//   *  2. The `BasicUpdateAction` object, in case we need to access any of its properties e.g. `getCheckPermissions()`.
//   *
//   * Our setter is responsible for matching by `id` to an existing record, combining existing data with new values,
//   * and storing the updated record. Optionally, if no existing record was found with the supplied id, it could throw an exception.
//   *
//   * If our records' unique identifying field was named something other than `id` (like `name` or `key`) then we'd pass
//   * that to the `BasicUpdateAction` constructor.
//   *
//   * Read more about how to implement your own `Update` action:
//   * @see \Civi\Api4\Generic\BasicUpdateAction
//   *
//   * @param bool $checkPermissions
//   *
//   * @return Generic\BasicUpdateAction
//   */
//  public static function update($checkPermissions = TRUE) {
//    return (new Generic\BasicUpdateAction(__CLASS__, __FUNCTION__, 'writeApi4exampleRecord'))
//      ->setCheckPermissions($checkPermissions);
//  }
//
//  /**
//   * `BasicSaveAction` allows multiple records to be created or updated at once.
//   *
//   * We pass it a setter function which is called once per record, so we can re-use the exact same function
//   * from our `Create` and `Update` actions. It takes two arguments:
//   *
//   *  1. The record to be creted or updated (as an array). Note that for existing records this array is not guaranteed
//   *     to contain existing data, only the `id` plus fields to be updated.
//   *  2. The `BasicSaveAction` object, in case we need to access any of its properties e.g. `getCheckPermissions()`.
//   *
//   * Our setter can tell the difference between a record to be created vs updated by the presence of an `id`.
//   *
//   * If our records' unique identifying field was named something other than `id` (like `name` or `key`) then we'd pass
//   * that to the `BasicSaveAction` constructor.
//   *
//   * Read more about how to implement your own `Save` action:
//   * @see \Civi\Api4\Generic\BasicSaveAction
//   *
//   * @param bool $checkPermissions
//   *
//   * @return Generic\BasicSaveAction
//   */
//  public static function save($checkPermissions = TRUE) {
//    return (new Generic\BasicSaveAction(__CLASS__, __FUNCTION__, 'writeApi4exampleRecord'))
//      ->setCheckPermissions($checkPermissions);
//  }
//
//  /**
//   * Our `Delete` action uses the `BasicBatchAction` class.
//   *
//   * There is no `BasicDeleteAction` because that isn't structurally different from other batch-style actions.
//   * The only difference is what the callback function does with the record passed to it.
//   *
//   * The callback for `BasicBatchAction` takes two arguments:
//   *  1. The record to be updated (as an array). Note this only contains an "id" plus fields to be updated,
//   *     not existing data unless the "reload" parameter is set.
//   *  2. The `BasicBatchAction` object, in case we need to access any of its properties e.g. `getCheckPermissions()`.
//   *
//   * Read more about batch actions:
//   * @see \Civi\Api4\Generic\BasicBatchAction
//   *
//   * @param bool $checkPermissions
//   *
//   * @return Generic\BasicBatchAction
//   */
//  public static function delete($checkPermissions = TRUE) {
//    return (new Generic\BasicBatchAction(__CLASS__, __FUNCTION__, 'deleteApi4exampleRecord'))
//      ->setCheckPermissions($checkPermissions);
//  }
//
//  /**
//   * Unlike the other Basic action classes, `Replace` does not require any callback.
//   *
//   * This is because it calls `Get`, `Save` and `Delete` internally - those must be defined for an entity to implement `Replace`.
//   *
//   * Read more about the `Replace` action:
//   * @inheritDoc
//   * @see \Civi\Api4\Generic\BasicReplaceAction
//   * @return Generic\BasicReplaceAction
//   */
//  public static function replace($checkPermissions = TRUE) {
//    return (new Generic\BasicReplaceAction(__CLASS__, __FUNCTION__))
//      ->setCheckPermissions($checkPermissions);
//  }

}
