<?php
namespace Civi\Api4;

/**
 * ShareChange entity.
 *
 * Provided by the CiviShare extension.
 *
 * @package Civi\Api4
 */
class ShareChange extends Generic\DAOEntity {

  // CHANGE STATUS TYPES
  // @todo add inline docs with explanation

  /** @var string This change has been recorded locally, needs to be sent */
  public const STATUS_LOCAL     = 'LOCAL';

  /** @var string This change is received, but has not been touched otherwise */
  public const STATUS_PENDING   = 'PENDING';

  /** @var string This change is currently being worked on, and should be left alone from other processes */
  public const STATUS_BUSY      = 'BUSY';

  /** @var string This change has been recorded locally, and should be sent out */
  public const STATUS_FORWARD   = 'FORWARD';

  /** @var string This change has been processed,  */
  public const STATUS_DONE      = 'DONE';

  /** @var string This change has been recorded locally, and should be sent out */
  public const STATUS_PROCESSED = 'PROCESSED';

  /** @var string This change has been recorded locally, and should be sent out */
  public const STATUS_DROPPED   = 'DROPPED';

  /** @var string This change has been recorded locally, and should be sent out */
  public const STATUS_ERROR     = 'ERROR';

  const ACTIVE_STATUS = [self::STATUS_LOCAL, self::STATUS_PENDING, self::STATUS_BUSY, self::STATUS_FORWARD];
  const COMPLETED_STATUS = [self::STATUS_DONE, self::STATUS_DROPPED, self::STATUS_ERROR, self::STATUS_PROCESSED];


  public const CHANGE_TYPE_TEST         = 'civishare.change.test';          // TESTING ONLY
  public const CHANGE_TYPE_CONTACT_BASE = 'civishare.change.contact.base';  // contact base data, no linked entities (like email)
}
