<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 27/11/2016
 * Time: 20:07
 */

namespace Ecfectus\Framework\Http\Middleware;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ResponseConverter
{
    public function __invoke(Request $request, callable $next)
    {
        $result = $next($request);

        if($result instanceof Response){
            return $result;
        }

        switch(gettype($result)){
            case 'array':
            case 'object':
                $result = json_encode($result);
                $response = new Response();
                $response->headers->set('Content-Type', 'application/json');
                $response->headers->set('Content-Length', strlen($result));
                return $response->setContent($result);
                break;
            default:
                $response = new Response();
                $response->headers->set('Content-Length', strlen((string) $result));
                return $response->setContent((string) $result);
        }
    }
}