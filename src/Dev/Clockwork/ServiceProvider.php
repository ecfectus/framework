<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 10/08/2017
 * Time: 14:59
 */

namespace Ecfectus\Framework\Dev\Clockwork;

use Clockwork\Clockwork;
use Clockwork\DataSource\PhpDataSource;
use Clockwork\Storage\FileStorage;
use Clockwork\Storage\StorageInterface;
use Ecfectus\Container\ServiceProvider\AbstractServiceProvider;
use Ecfectus\Container\ServiceProvider\BootableServiceProviderInterface;
use Ecfectus\Framework\Application;
use Ecfectus\Framework\Http\KernelInterface;
use Ecfectus\Router\RouterInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;

class ServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    public $provides = [
        Clockwork::class,
        PhpDataSource::class,
        StorageInterface::class,
        DataSource::class,
    ];

    public function register()
    {
        $this->share(PhpDataSource::class, function(){
            return new PhpDataSource();
        });

        $this->share(DataSource::class, [function(Application $app){
            return new DataSource($app);
        }, Application::class]);

        $this->share(StorageInterface::class, function(){
            return new FileStorage(app_path('storage/clockwork'));
        });

        $this->share(Clockwork::class, [function(PhpDataSource $phpDataSource, DataSource $ecfectusSource, StorageInterface $storage){
            $clock = new Clockwork();
            $clock->addDataSource($phpDataSource);
            $clock->addDataSource($ecfectusSource);
            $clock->setStorage($storage);
            return $clock;
        }, PhpDataSource::class, DataSource::class, StorageInterface::class]);

        // Force clockwork to start
        $clockwork = $this->getContainer()->get(Clockwork::class);
    }

    public function boot()
    {

        $kernel = $this->getContainer()->get(KernelInterface::class);
        array_unshift($kernel->globalMiddleware, Middleware::class);

        $router = $this->getContainer()->get(RouterInterface::class);

        $router->get('__clockwork/{id}')->setHandler(function(Request $request){
            try{
                $values = $request->attributes->get('route')->getValues();
                $storage = app(StorageInterface::class);
                $data = $storage->retrieve($values['id']);
                return $data->toArray();
            }catch(\Throwable $e){
                return 'failed' . $e->getMessage();
            }
        });

        //Kill all files over last 5 logs
        $files = array_slice(iterator_to_array(Finder::create()->files()->in(app_path('storage/clockwork'))->sortByModifiedTime()->name('*.json')), 0, -5);
        foreach($files as $file){
            unlink($file->getRealPath());
        }
    }
}