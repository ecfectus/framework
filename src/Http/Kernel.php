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
use Ecfectus\Pipeline\LastArgumentPipeline;
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

    ];

    /**
     * Named middleware to be used selectively via routes.
     *
     * @var array
     */
    public $middleware = [

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

            $this->router->prepare();

            $response = $this->app->get(Response::class);

            $pipeline = $this->createPipeline();

            try{

                $route = $this->router->match($request->getHost() . $request->getPathInfo(), $request->getMethod());

                $this->events->fire(new RouteMatched($route));

                $request->attributes->add(['route' => $route]);

                //add the routes middleware to the pipeline
                foreach($route->getMiddleware() as $routeMiddleware){
                    $pipeline->push($routeMiddleware);
                }

                //finally add the route handler to the pipeline as the last to act
                $pipeline->push($this->buildRouteHandler($route->getHandler()));

            }catch( NotFoundException $e){
                $this->events->fire(new RouteNotFound());
                $response->setStatusCode(404);
            }catch( MethodNotAllowedException $e){
                $this->events->fire(new RouteMethodNotAllowed($e));
                $response->headers->set('ALLOW', implode(', ', $e->getMethods()));
                return $response->setStatusCode(405);
            }

            $response = $pipeline($request, $response);

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
        $pipeline = $this->app->get(LastArgumentPipeline::class);
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
        return function(Request $request, Response $response, callable $next) use ($handler){
            $handler = $this->app->resolve($handler);
            $response = $this->convertResponseIfNeeded($handler($request, $response), $response);
            return $next($request, $response);
        };
    }

    private function convertResponseIfNeeded($result, $response)
    {
        if($result instanceof Response){
            return $result;
        }

        switch(gettype($result)){
            case 'array':
            case 'object':
                $result = json_encode($result);
                $response->headers->set('Content-Type', 'application/json');
                $response->headers->set('Content-Length', strlen($result));
                return $response->setContent($result);
                break;
            default:
                return $response->setContent((string) $result);
        }
    }

    /**
     * @inheritDoc
     */
    public function terminate(Request $request, Response $response)
    {
        $this->events->fire(new Terminate($request, $response));
    }

}