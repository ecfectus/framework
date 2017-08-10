<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 08/04/16
 * Time: 19:44
 */

namespace Ecfectus\Framework;


use Ecfectus\Container\Container;
use Ecfectus\Container\ContainerInterface;
use Ecfectus\Container\ReflectionContainer;
use Ecfectus\Container\ServiceProviderContainer;
use Ecfectus\Events\DispatcherInterface;
use Ecfectus\Framework\Bootstrap\Events\AfterBootstrap;
use Ecfectus\Framework\Bootstrap\Events\BeforeBootstrap;
use Ecfectus\Framework\Bootstrap\HandleExceptions;
use Ecfectus\Framework\Bootstrap\LoadEnvValues;
use Ecfectus\Framework\Config\ConfigServiceProvider;
use Ecfectus\Framework\Config\RepositoryInterface;
use Ecfectus\Framework\Event\EventServiceProvider;
use Ecfectus\Framework\Exceptions\ExceptionsServiceProvider;

class Application extends Container
{
    protected static $instance = null;

    protected $hasBeenBootstrapped = false;

    protected $bootstrappers = [
        HandleExceptions::class,
        LoadEnvValues::class,
    ];

    /**
     * Set the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    /**
     * Set the shared instance of the container.
     *
     * @param  Ecfectus\Container\ContainerInterface  $container
     * @return static
     */
    public static function setInstance(ContainerInterface $container = null)
    {
        return static::$instance = $container;
    }

    public function __construct($path = ''){

        static::setInstance($this);

        $this->share(ReflectionContainer::class, new ReflectionContainer());
        $this->share(ServiceProviderContainer::class, new ServiceProviderContainer());

        $this->delegate($this->get(ServiceProviderContainer::class));

        $this->delegate($this->get(ReflectionContainer::class));

        // bind self into self - trippy
        $this->share(Application::class, $this);

        $this->share('app', $this);

        $this->share(ContainerInterface::class, $this);
        $this->share(\Interop\Container\ContainerInterface::class, $this);

        // bind the paths
        $this->share('path', $path);
        $this->share('path.config', $path . DIRECTORY_SEPARATOR . 'config');
        $this->share('path.storage', $path . DIRECTORY_SEPARATOR . 'storage');

        // add exceptions, config and event service providers as everything else will rely on them
        $this->addServiceProvider(ExceptionsServiceProvider::class);

        $this->addServiceProvider(ConfigServiceProvider::class);

        $this->addServiceProvider(EventServiceProvider::class);

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

    /**
     * Determine if the application has been bootstrapped before.
     *
     * @return bool
     */
    public function hasBeenBootstrapped() : bool
    {
        return $this->hasBeenBootstrapped;
    }

    public function bootstrap(){

        if($this->hasBeenBootstrapped()){
            return;
        }

        $this->get(DispatcherInterface::class)->fire(new BeforeBootstrap($this));

        $this->runBootstrappers();

        $this->hasBeenBootstrapped = true;

        $providers = $this->get(RepositoryInterface::class)->get('app.providers', []);

        foreach($providers as $provider){
            $this->addServiceProvider($provider);
        }

        $this->bootServiceProviders();

        $this->get(DispatcherInterface::class)->fire(new AfterBootstrap($this));
    }

    private function runBootstrappers()
    {
        foreach ($this->bootstrappers as $bootstrapper) {
            $bootstrapper = new $bootstrapper();
            $bootstrapper->bootstrap($this);
        }
    }

    public function runningInConsole() : bool
    {
        return php_sapi_name() == 'cli' || php_sapi_name() == 'phpdbg';
    }

}