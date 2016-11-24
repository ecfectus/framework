<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 14/10/16
 * Time: 13:59
 */

namespace Ecfectus\Framework\Http\Events;


use Ecfectus\Events\Event;
use Ecfectus\Router\MethodNotAllowedException;

class RouteMethodNotAllowed extends Event
{
    public $exception = null;

    public function __construct(MethodNotAllowedException $exception)
    {
        $this->exception = $exception;
    }

}