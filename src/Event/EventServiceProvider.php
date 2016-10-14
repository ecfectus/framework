<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 11/10/16
 * Time: 14:10
 */

namespace Ecfectus\Framework\Event;

use Ecfectus\Container\ServiceProvider\AbstractServiceProvider;
use Ecfectus\Events\Dispatcher;
use Ecfectus\Events\DispatcherInterface;
use Ecfectus\Framework\Application;

class EventServiceProvider extends AbstractServiceProvider
{

    public $provides = [
        DispatcherInterface::class
    ];

    public function register()
    {
        $this->share(DispatcherInterface::class, [function(Application $app) {

            return (new Dispatcher())
                ->setResolver(function($callback = null) use ($app){
                    return $app->resolve($callback);
                });

        }, Application::class]);
    }

}