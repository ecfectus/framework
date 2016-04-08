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

    protected $middleware = [];

    public function __construct(Application $app){
        $this->app = $app;
    }

    public function handle(RequestInterface $request, ResponseInterface $response){

        $router = $this->app->get(Router::class);

        $route = $router->matchRequest($request);

        $request = $request->withAttribute('route', $route);

        $this->runner = new Runner($this->middleware);

        $this->runner->setContainer($this->app);


        $this->runner->addMiddleware(function($request, $response, $next) {


            return $response->getBody()->write(print_r($request->getAttribute('route'), true));
        });

        $this->server = new Server(function($request, $response, $done){

            $runner = $this->runner;

            return $runner($request, $response);

        }, $request, $response);


        $this->server->listen();
    }
}