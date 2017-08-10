<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 11/10/16
 * Time: 16:58
 */

namespace Ecfectus\Framework\Exceptions;

use Ecfectus\Container\ServiceProvider\AbstractServiceProvider;
use Ecfectus\Framework\Config\RepositoryInterface;
use Symfony\Component\Finder\Finder;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

class ExceptionsServiceProvider extends AbstractServiceProvider
{

    public $provides = [
        Handler::class
    ];

    public function register()
    {
        $this->share(Handler::class, function() {
            return new Handler();
        });

        $this->share(Run::class, function(){
            $whoops = new Run();
            $whoops->writeToOutput(false);
            $whoops->allowQuit(false);
            return $whoops;
        });

        $this->share(PrettyPageHandler::class, [function(RepositoryInterface $config){
            $handler = new PrettyPageHandler();
            $handler->addDataTable('Config', $config->toArray());
            $directories = [];
            foreach (Finder::create()->in(app_path())->exclude('vendor')->directories()->depth(0) as $dir) {
                $directories[] = $dir->getPathname();
            }
            $handler->setApplicationPaths($directories);
            return $handler;
        }, RepositoryInterface::class]);
    }

}