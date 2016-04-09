<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 09/04/16
 * Time: 18:33
 */

namespace Ecfectus\Event;


use Ecfectus\Container\ServiceProvider\AbstractServiceProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventServiceProvider extends AbstractServiceProvider
{

    public $provides = [
        EventDispatcherInterface::class
    ];

    public function register(){
        $this->share(EventDispatcherInterface::class, function(){
            return new EventDispatcher();
        });
    }

}