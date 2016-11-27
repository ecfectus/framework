<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 11/10/16
 * Time: 16:50
 */

namespace Ecfectus\Framework\Http;


use Ecfectus\Container\ContainerInterface;
use Ecfectus\Events\DispatcherInterface;
use Ecfectus\Framework\Http\Events\RouteMatched;
use Ecfectus\Framework\Http\Events\RouteMethodNotAllowed;
use Ecfectus\Framework\Http\Events\RouteNotFound;
use Ecfectus\Framework\Http\Events\Terminate;
use Ecfectus\Framework\Http\Middleware\ResponseConverter;
use Ecfectus\Framework\Session\Http\Middleware\StartSessionMiddleware;
use Ecfectus\Pipeline\PipelineInterface;
use Ecfectus\Router\MethodNotAllowedException;
use Ecfectus\Router\NotFoundException;
use Ecfectus\Router\RouterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Kernel implements KernelInterface
{
    /**
     * @var ContainerInterface|null
     */
    protected $app = null;

    /**
     * @var RouterInterface|null
     */
    protected $router = null;

    /**
     * @var DispatcherInterface|null
     */
    protected $events = null;

    /**
     * Global middleware to run on every request.
     *
     * @var array
     */
    public $globalMiddleware = [
        ResponseConverter::class,
    ];

    /**
     * Named middleware to be used selectively via routes.
     *
     * @var array
     */
    public $middleware = [
        'session' => StartSessionMiddleware::class,
    ];

    /**
     * Grouped middleware to be used selectivly via routes.
     *
     * @var array
     */
    public $middlewareGroups = [

    ];

    /**
     * @param ContainerInterface $app
     */
    public function __construct(ContainerInterface $app, RouterInterface $router, DispatcherInterface $events)
    {
        $this->app = $app;
        $this->router = $router;
        $this->events = $events;
    }

    /**
     * @inheritDoc
     */
    public function handle(Request $request) : Response
    {
        try{
            $request->enableHttpMethodParameterOverride();

            $this->app->bind(Request::class, $request);

            $this->router->prepare();

            $pipeline = $this->createPipeline();

            try{

                $route = $this->matchRoute($request, $pipeline);

                $request->attributes->add(['route' => $route]);

            }catch( NotFoundException $e){
                $this->events->fire(new RouteNotFound());
                $pipeline->push(function(Request $request){
                    return (new Response())->setStatusCode(404)->setContent('404 Not Found');
                });
            }catch( MethodNotAllowedException $e){
                $this->events->fire(new RouteMethodNotAllowed($e));

                $pipeline->push(function(Request $request) use ($e){
                    $response = new Response();
                    $response->headers->set('ALLOW', implode(', ', $e->getMethods()));
                    return $response->setStatusCode(405);
                });
            }

            $response = $pipeline($request);

            return $response->prepare($request);

        }catch( \Throwable $e ){
            //throw $e;
            return $this->app->get(Response::class)
                ->setStatusCode(500)
                ->setContent($e->getMessage())
                ->prepare($request);
        }
    }

    private function createPipeline() : PipelineInterface
    {
        $pipeline = $this->app->get(PipelineInterface::class);
        foreach($this->globalMiddleware as $middleware){
            $pipeline->push($middleware);
        }
        return $pipeline;
    }

    /**
     * wrap the route handler in a closure so we can pass just the request and response objects, no need return through $next.
     *
     * @param $handler
     * @return \Closure
     */
    private function buildRouteHandler($handler)
    {
        return function(Request $request) use ($handler){
            $handler = $this->app->resolve($handler);
            return $handler($request);
        };
    }

    /**
     * @inheritDoc
     */
    public function terminate(Request $request, Response $response)
    {
        $this->events->fire(new Terminate($request, $response));
    }

    /**
     * @param Request $request
     * @param $pipeline
     * @return \Ecfectus\Router\RouteInterface
     */
    private function matchRoute(Request $request, $pipeline)
    {
        $route = $this->router->match($request->getHost() . $request->getPathInfo(), $request->getMethod());

        $this->events->fire(new RouteMatched($route));

        //add the routes middleware to the pipeline
        foreach ($route->getMiddleware() as $routeMiddleware) {
            $this->pushToPipeline($pipeline, $routeMiddleware);
        }

        //finally add the route handler to the pipeline as the last to act
        $pipeline->push($this->buildRouteHandler($route->getHandler()));

        return $route;
    }

    /**
     * @param $pipeline
     * @param $routeMiddleware
     */
    private function pushToPipeline($pipeline, $routeMiddleware)
    {
        // If its named in the kernel groups
        if (is_string($routeMiddleware) && in_array($routeMiddleware, array_keys($this->middlewareGroups))) {
            foreach ($this->middlewareGroups[$routeMiddleware] as $groupMiddleware) {
                $pipeline->push($groupMiddleware);
            }
            return;
        }

        // If its named in the kernel
        if (is_string($routeMiddleware) && in_array($routeMiddleware, array_keys($this->middleware))) {
            $pipeline->push($this->middleware[$routeMiddleware]);
            return;
        }

        //else its from somewhere else
        $pipeline->push($routeMiddleware);
    }

}