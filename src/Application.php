<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 08/04/16
 * Time: 19:44
 */

namespace Ecfectus;


use Ecfectus\Config\RepositoryInterface;
use Ecfectus\Container\Container;
use Ecfectus\Container\ReflectionContainer;
use Ecfectus\Container\ServiceProviderContainer;
use Ecfectus\Config\ConfigServiceProvider;
use Ecfectus\Router\Router;
use Interop\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Server;

class Application extends Container
{

    protected $hasBeenBootstrapped = false;

    protected $queue = null;

    public function __construct($path = ''){

        $this->delegate(new ServiceProviderContainer());

        $this->delegate(new ReflectionContainer());

        // bind self into self - trippy
        $this->share(Application::class, $this);

        $this->share('app', $this);

        $this->share(ContainerInterface::class, $this);

        // bind the paths
        $this->share('path', $path);
        $this->share('path.config', $path . DIRECTORY_SEPARATOR . 'config');

        // add config service provider as everything else will rely on it
        $this->addServiceProvider(ConfigServiceProvider::class);

        $this->queue = new \SplQueue();
    }

    public function resolve($entry){

        if(is_string($entry) && strpos($entry, '@') !== false){
            list($class, $method) = explode('@', $entry);
            $instance = $this->resolve($class);
            return function(...$arguments) use ($instance, $method){
                return $instance->$method(...$arguments);
            };
        }

        if(is_callable($entry)){
            return $entry;
        }

        //dont do a has check to ensure exception gets thrown

        return $this->get($entry);
    }

    public function push($path, $middleware = null){

        if (null === $middleware) {
            $middleware = $path;
            $path       = '/';
        }

        $this->queue->enqueue([
            'path' => $this->normalizePipePath($path),
            'middleware' => $middleware
        ]);

        return $this;
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

        if($this->queue->isEmpty()){
            return $response;
        }

        $entry = $this->queue->dequeue();

        if(!$this->isValidForPath($entry['path'], $request)){
            return $this($request, $response, $this);
        }

        $middleware = $this->resolve($entry['middleware']);

        return $middleware($request, $response, $this);
    }

    private function isValidForPath($path, RequestInterface $request){
        if($path == '/'){
            return true;
        }

        $requestPath = $request->getUri()->getPath() ?: '/';

        if (substr(strtolower($requestPath), 0, strlen($path)) === strtolower($path)) {
            return true;
        }

        return false;
    }

    /**
     * Normalize a path used when defining a pipe
     *
     * Strips trailing slashes, and prepends a slash.
     *
     * @param string $path
     * @return string
     */
    private function normalizePipePath($path)
    {
        // Prepend slash if missing
        if (empty($path) || $path[0] !== '/') {
            $path = '/' . $path;
        }
        // Trim trailing slash if present
        if (strlen($path) > 1 && '/' === substr($path, -1)) {
            $path = rtrim($path, '/');
        }
        return $path;
    }

    /**
     * Determine if the application has been bootstrapped before.
     *
     * @return bool
     */
    public function hasBeenBootstrapped()
    {
        return $this->hasBeenBootstrapped;
    }

    public function bootstrap(){

        if($this->hasBeenBootstrapped()){
            return;
        }

        $this->hasBeenBootstrapped = true;

        $providers = $this->get(RepositoryInterface::class)->get('app.providers');

        foreach($providers as $provider){
            $this->addServiceProvider($provider);
        }

        $this->bootServiceProviders();
    }

    public function listen(){

        echo 'working';

        $this->bootstrap();

        $request = $this->get(ServerRequestInterface::class);

        $response = $this->get(ResponseInterface::class);


        // add route matching after bootstrap
        $this->queue->add(0, ['path' => '/', 'middleware' => function($request, $response, $next){

            $router = $this->get(Router::class);

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
                        $this->push($middleware);
                    }
                    //then finally add the route handler
                    $this->push($route[1]->getCallable());
                    break;
                case 2:
                    $response = $response->withStatus(405)->withHeader('Allow', implode(', ', $route[1]));
                    break;
            }

            return $next($request, $response);
        }]);

        $server = new Server($this, $request, $response);

        $server->listen();
    }

}