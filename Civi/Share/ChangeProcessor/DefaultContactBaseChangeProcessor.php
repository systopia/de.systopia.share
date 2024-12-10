<?php
namespace Civi\Share\ChangeProcessor;

use Civi\Core\Event\GenericHookEvent as Event;
use Civi\Api4\ShareChange;
use Civi\Api4\ShareNode;

class DefaultContactBaseChangeProcessor extends ChangeProcessorBase
{
  /**
   * Process the given change if you can/should/want and don't forget to mark processed when done
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
