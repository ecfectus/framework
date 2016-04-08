<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 08/04/16
 * Time: 19:44
 */

namespace Ecfectus\Framework;


use Ecfectus\Container\Container;
use Ecfectus\Container\ReflectionContainer;
use Ecfectus\Container\ServiceProviderContainer;
use Ecfectus\Framework\Config\ConfigServiceProvider;
use Interop\Container\ContainerInterface;

class Application extends Container
{

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
    }

}