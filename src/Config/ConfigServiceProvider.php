<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 08/04/16
 * Time: 19:53
 */

namespace Ecfectus\Framework\Config;

use Ecfectus\Container\ServiceProvider\AbstractServiceProvider;
use Symfony\Component\Finder\Finder;

class ConfigServiceProvider extends AbstractServiceProvider
{

    public $provides = [
        RepositoryInterface::class
    ];

    public function register()
    {
        $this->share(RepositoryInterface::class, [function($path) {

            $data = [];
            $configPath = realpath($path);
            foreach (Finder::create()->files()->name('*.php')->in($configPath) as $file) {
                $name = $file->getBasename('.php');
                $data[$name] = require $file->getRealPath();
            }

            return new Repository($data);

        }, 'path.config']);
    }

}