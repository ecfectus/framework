<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 08/04/16
 * Time: 20:59
 */

namespace Ecfectus\Framework\Http;


use Ecfectus\Container\ServiceProvider\AbstractServiceProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

class HttpServiceProvider extends AbstractServiceProvider
{

    public $provides = [
        ServerRequestInterface::class,
        ResponseInterface::class
    ];

    public function register(){
        $this->bind(ServerRequestInterface::class, function(){
           return ServerRequestFactory::fromGlobals();
        });

        $this->bind(ResponseInterface::class, function(){
            return new Response();
        });
    }

}