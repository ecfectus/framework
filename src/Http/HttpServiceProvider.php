<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 11/10/16
 * Time: 16:58
 */

namespace Ecfectus\Framework\Http;


use Ecfectus\Container\ContainerInterface;
use Ecfectus\Container\ServiceProvider\AbstractServiceProvider;
use Ecfectus\Container\ServiceProvider\BootableServiceProviderInterface;
use Ecfectus\Events\DispatcherInterface;
use Ecfectus\Framework\View\ViewEngineInterface;
use Ecfectus\Router\RouterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HttpServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{

    public $provides = [
        KernelInterface::class
    ];

    public function register()
    {
        $this->share(KernelInterface::class, [function(ContainerInterface $app, RouterInterface $router, DispatcherInterface $events) {

            return new Kernel($app, $router, $events);

        }, ContainerInterface::class, RouterInterface::class, DispatcherInterface::class]);

        $this->bind(Request::class, function(){
            return new Request();
        });

        $this->bind('server.request', function(){
            return Request::createFromGlobals();
        });

        $this->bind(Response::class, function(){
            return new Response();
        });
    }

    public function boot()
    {

    }

}