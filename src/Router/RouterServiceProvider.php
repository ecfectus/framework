<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 11/10/16
 * Time: 18:43
 */

namespace Ecfectus\Framework\Router;


use Ecfectus\Container\ServiceProvider\AbstractServiceProvider;
use Ecfectus\Router\Router;
use Ecfectus\Router\RouterInterface;

class RouterServiceProvider extends AbstractServiceProvider
{
    public $provides = [
        RouterInterface::class
    ];

    public function register()
    {
        $this->share(RouterInterface::class, function () {

            return new Router();

        });
    }
}