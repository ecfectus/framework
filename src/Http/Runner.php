<?php namespace Ecfectus\Http;

use Interop\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Runner
{
    /**
     * @var array
     */
    protected $middlewares = [];

    /**
     * @var null
     */
    protected $container = null;

    /**
     * @param array $middlewares
     */
    public function __construct($middlewares = [])
    {
        $this->middlewares = $middlewares;
    }

    /**
     * @param $middleware
     */
    public function addMiddleware($middleware)
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * @return array
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Run the added middlewares
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return Response
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response)
    {
        $entry = array_shift($this->middlewares);
        $middleware = $this->resolve($entry);
        return $middleware($request, $response, $this);
    }

    private function resolve($entry){

        if (! $entry) {
            return function (
                RequestInterface $request,
                ResponseInterface $response,
                callable $next
            ) {
                return $response;
            };
        }

        if(is_string($entry) && strpos($entry, '@') !== false){
            list($class, $method) = explode('@', $entry);
            $instance = $this->resolve($class);
            return function($request, $response, $next) use ($instance, $method){
                return $instance->$method($request, $response, $next);
            };
        }

        if(is_callable($entry)){
            return $entry;
        }

        if(null !== $this->container && $this->container->has($entry)){
            return $this->container->get($entry);
        }

        if(!class_exists($entry)){
            throw new \InvalidArgumentException(sprintf('The middleware %s isn\'t a callable or class string.', (string) $entry));
        }

        return new $entry();

    }
}
