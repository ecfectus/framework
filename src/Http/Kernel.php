<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 08/04/16
 * Time: 19:43
 */

namespace Ecfectus\Framework\Http;


use Ecfectus\Framework\Application;
use Ecfectus\MiddlewareRunner\Runner;
use Ecfectus\Router\Router;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Server;

class Kernel
{
    protected $app;

    protected $server;

    protected $runner;

    public function __construct(Application $app){
        $this->app = $app;
    }

    public function handle(RequestInterface $request, ResponseInterface $response){

        $this->runner = $this->app->get(Runner::class);

        $this->runner->setContainer($this->app);

        $router = $this->app->get(Router::class);

        $this->runner->addMiddleware(function($request, $response, $next) use ($router) {

            $route = $router->matchRequest($request);

            return $response->getBody()->write(print_r($route, true));
        });

        $this->server = new Server(function($request, $response, $done){

            return $this->runner($request, $response);

            //$response = $response->getBody()->write('it works!');

            //return $response;

        }, $request, $response);


        $this->server->listen();
    }
}