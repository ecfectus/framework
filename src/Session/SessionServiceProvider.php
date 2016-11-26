<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 26/11/2016
 * Time: 22:56
 */

namespace Ecfectus\Framework\Session;


use Ecfectus\Container\ServiceProvider\AbstractServiceProvider;
use Ecfectus\Framework\Config\RepositoryInterface;
use Ecfectus\Framework\Session\Http\Middleware\StartSessionMiddleware;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

class SessionServiceProvider extends AbstractServiceProvider
{
    public $provides = [
        SessionInterface::class,
        SessionStorageInterface::class,
        \SessionHandlerInterface::class,
        StartSessionMiddleware::class
    ];

    public function register()
    {
        $this->share(SessionInterface::class, [function(SessionStorageInterface $storage){
            return new Session($storage);
        }, SessionStorageInterface::class]);

        $this->share(SessionStorageInterface::class, [function(RepositoryInterface $config, \SessionHandlerInterface $handler){
            return new NativeSessionStorage($config->get('session.options', []), $handler);
        }, RepositoryInterface::class, \SessionHandlerInterface::class]);

        $this->share(\SessionHandlerInterface::class, [function(RepositoryInterface $config, $path){
            return new NativeFileSessionHandler($config->get('session.save_path', $path . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'session'));
        }, RepositoryInterface::class, 'path.storage']);

        $this->bind(StartSessionMiddleware::class, [function(SessionInterface $session){
            return new StartSessionMiddleware($session);
        }, SessionInterface::class]);
    }

}