<?php

namespace Ecfectus\Test\Http;


use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class TestClass
{

    public function __construct(\stdClass $obj){
        $this->obj = $obj;
    }

    public function __invoke(RequestInterface $request, ResponseInterface $response, $next){
        return $next($request, $response);
    }

}