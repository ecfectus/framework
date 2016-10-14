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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HttpServiceProvider extends AbstractServiceProvider
{

    public $provides = [
        KernelInterface::class
    ];

    public function register()
    {
        $this->share(KernelInterface::class, [function(ContainerInterface $app) {

            return new Kernel($app);

        }, ContainerInterface::class]);

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

}