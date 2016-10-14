<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 14/10/16
 * Time: 13:59
 */

namespace Ecfectus\Framework\Http\Events;


use Ecfectus\Events\Event;
use Ecfectus\Router\Route;

class RouteMatched extends Event
{

    public $route = null;

    public function __construct(Route $route)
    {
        $this->route = $route;
    }

}