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

    public function pushMiddleware($middleware){
        $this->middleware[] = $middleware;
        return $this;
    }

    public function prependMiddleware($middleware){
        array_unshift($this->middleware, $middleware);
        return $this;
    }

    public function handle(RequestInterface $request, ResponseInterface $response){

        //first we find the route, and if not set the status codes

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
                break;
            case 2:
                $response = $response->withStatus(405)->withHeader('Allow', implode(', ', $route[1]));
                break;
        }

        // then we create the middleware runner

        $this->runner = new Runner($this->middleware);

        $this->runner->setContainer($this->app);


        $this->runner->addMiddleware(function($request, $response, $next) {

            return $response->getBody()->write(print_r($request, true));
        });

        // and run the middlewares against the request

        $this->server = new Server(function($request, $response, $done){

            $runner = $this->runner;

            return $runner($request, $response);

        }, $request, $response);


        $this->server->listen();
    }
}