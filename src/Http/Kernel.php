<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 08/04/16
 * Time: 19:43
 */

namespace Ecfectus\Http;


use Ecfectus\Application;
use Ecfectus\Http\Runner;
use Ecfectus\Router\Route;
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

    public function pushMiddleware($middleware){
        $this->middleware[] = $middleware;
        return $this;
    }

    public function prependMiddleware($middleware){
        array_unshift($this->middleware, $middleware);
        return $this;
    }

    public function handle(RequestInterface $request, ResponseInterface $response){

        // we create the middleware runner

        $this->runner = new Runner($this->middleware);

        // then we find the route, and if not set the status codes

        $router = $this->app->get(Router::class);

        $route = $router->matchRequest($request);

        switch($route[0]){
            case 0:
                $response = $response->withStatus(404);
                break;
            case 1:
                $request = $request->withAttribute('route', $route[1]);
                foreach((array) $route[2] as $key => $val){
                    $request = $request->withAttribute($key, $val);
                }
                //add any route middleware
                foreach($route[1]->getMiddleware() as $middleware){
                    $this->runner->addMiddleware($middleware);
                }
                //then finally add the route handler
                $this->runner->addMiddleware($route[1]->getCallable());
                break;
            case 2:
                $response = $response->withStatus(405)->withHeader('Allow', implode(', ', $route[1]));
                break;
        }

        $this->runner->setContainer($this->app);

        // and run the middlewares against the request

        $this->server = new Server(function($request, $response, $done){

            $runner = $this->runner;

            return $runner($request, $response);

        }, $request, $response);


        $this->server->listen();
    }
}