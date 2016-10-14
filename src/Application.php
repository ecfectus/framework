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
use Ecfectus\Framework\Config\ConfigServiceProvider;
use Ecfectus\Framework\Config\RepositoryInterface;
use Ecfectus\Framework\Event\EventServiceProvider;

class Application extends Container
{

    protected $hasBeenBootstrapped = false;

    public function __construct($path = ''){

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

        // add config and event service providers as everything else will rely on them
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

        $this->hasBeenBootstrapped = true;

        $providers = $this->get(RepositoryInterface::class)->get('app.providers');

        foreach($providers as $provider){
            $this->addServiceProvider($provider);
        }

        $this->bootServiceProviders();

        $this->get(DispatcherInterface::class)->fire(new AfterBootstrap($this));
    }

}