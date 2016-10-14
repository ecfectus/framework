<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 11/10/16
 * Time: 14:20
 */

namespace Ecfectus\Framework\Bootstrap\Events;


use Ecfectus\Events\Event;
use Ecfectus\Framework\Application;

class BeforeBootstrap extends Event
{

    public $app = null;

    public function __construct(Application $app){
        $this->app = $app;
    }

}