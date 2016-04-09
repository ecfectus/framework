<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 08/04/16
 * Time: 20:59
 */

namespace Ecfectus\Http;


use Ecfectus\Container\ServiceProvider\AbstractServiceProvider;
use Ecfectus\Router\Router;
use FastRoute\RouteParser\Std;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use FastRoute\DataGenerator\GroupCountBased;

class HttpServiceProvider extends AbstractServiceProvider
{

    public $provides = [
        ServerRequestInterface::class,
        ResponseInterface::class,
        Router::class
    ];

    public function register(){
        $this->bind(ServerRequestInterface::class, function(){
           return ServerRequestFactory::fromGlobals();
        });

        $this->bind(ResponseInterface::class, function(){
            return new Response();
        });

        $this->share(Router::class, function(){
            return new Router(new Std(), new GroupCountBased());
        });
    }

}