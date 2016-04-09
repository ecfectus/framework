<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 08/04/16
 * Time: 19:53
 */

namespace Ecfectus\Config;

use Ecfectus\Container\ServiceProvider\AbstractServiceProvider;

class ConfigServiceProvider extends AbstractServiceProvider
{

    public $provides = [
        RepositoryInterface::class
    ];

    public function register()
    {
        $this->share(RepositoryInterface::class, [function($path)
        {

            return new Repository(
                new FileLoader(
                    $path
                )
            );

        }, 'path.config']);
    }

}