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

  // todo: explanations
  public const STATUS_LOCAL     = 'LOCAL';
  public const STATUS_PENDING   = 'PENDING';
  public const STATUS_BUSY      = 'BUSY';
  public const STATUS_FORWARD   = 'FORWARD';
  public const STATUS_DONE      = 'DONE';
  public const STATUS_PROCESSED = 'PROCESSED';
  public const STATUS_DROPPED   = 'DROPPED';
  public const STATUS_ERROR     = 'ERROR';

  const ACTIVE_STATUS = [self::STATUS_LOCAL, self::STATUS_PENDING, self::STATUS_BUSY, self::STATUS_FORWARD];
  const COMPLETED_STATUS = [self::STATUS_DONE, self::STATUS_DROPPED, self::STATUS_ERROR, self::STATUS_PROCESSED];
}
