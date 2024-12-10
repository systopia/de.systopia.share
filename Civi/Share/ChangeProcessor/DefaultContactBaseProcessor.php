<?php
namespace Civi\Share;

use Civi\Core\Event\GenericHookEvent as Event;
use Civi\Api4\ShareChange;
use Civi\Api4\ShareNode;

class DefaultContactBaseChangeProcessor extends ChangeProcessorBase
{
  /**
   *
   * @param $processing_event
   * @param $event_type
   * @param $dispatcher
   * @return void
   */
  public function process_change($processing_event, $event_type, $dispatcher)
  {
    // TODO: Implement process_change() method.
    \Civi::log()->debug('test');
  }
}
