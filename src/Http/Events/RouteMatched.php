<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 14/10/16
 * Time: 13:59
 */

namespace Ecfectus\Framework\Http\Events;


use Ecfectus\Events\Event;
use Ecfectus\Router\RouteInterface;

class RouteMatched extends Event
{

    public $route = null;

    public function __construct(RouteInterface $route)
    {
        $this->route = $route;
    }

}