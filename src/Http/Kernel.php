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

class Kernel
{
    protected $app;

    public function __construct(Application $app){
        $this->app = $app;
    }

    private function bootstrap(){
        if (! $this->app->hasBeenBootstrapped()) {
            $this->app->bootstrap();
        }
    }

    public function handle(RequestInterface $request, ResponseInterface $response){

        $this->bootstrap();

        print_r($this);
    }
}