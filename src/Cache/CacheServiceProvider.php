<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 24/11/2016
 * Time: 22:16
 */

namespace Ecfectus\Framework\Cache;


use Ecfectus\Cache\CacheItemPoolInterface;
use Ecfectus\Cache\CacheManager;
use Ecfectus\Cache\CacheManagerInterface;
use Ecfectus\Container\ServiceProvider\AbstractServiceProvider;
use Ecfectus\Container\ServiceProvider\BootableServiceProviderInterface;
use Ecfectus\Events\DispatcherInterface;
use Ecfectus\Framework\Config\RepositoryInterface;
use Ecfectus\Framework\Http\Events\Terminate;

class CacheServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{

    public $provides = [
        CacheManagerInterface::class,
        CacheItemPoolInterface::class,
        \Psr\Cache\CacheItemPoolInterface::class
    ];

    public function register()
    {
        $this->share(CacheManagerInterface::class, [function(RepositoryInterface $config){
            return new CacheManager($config->get('cache', []));
        }, RepositoryInterface::class]);

        $this->share(CacheItemPoolInterface::class, [function(CacheManagerInterface $manager){
            return $manager;
        }, CacheManagerInterface::class]);

        $this->share(\Psr\Cache\CacheItemPoolInterface::class, [function(CacheManagerInterface $manager){
            return $manager;
        }, CacheManagerInterface::class]);
    }

    public function boot()
    {
        $this->getContainer()->get(DispatcherInterface::class)->listen(Terminate::class, function(Terminate $event){
            $this->getContainer()->get(CacheManagerInterface::class)->commit();
        });
    }

}