<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 08/04/16
 * Time: 19:43
 */

namespace Ecfectus\Framework\Http;


use Ecfectus\Framework\Application;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Server;

class Kernel
{
    protected $app;

    protected $server;

    public function __construct(Application $app){
        $this->app = $app;
    }

    public function handle(RequestInterface $request, ResponseInterface $response){

        $this->server = new Server(function($request, $response, $done){

            $response->getBody()->write('it works!');
            
        }, $request, $response);


        $this->server->listen();
    }
}