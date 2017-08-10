<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 27/11/2016
 * Time: 20:07
 */

namespace Ecfectus\Framework\Dev\Clockwork;

use Clockwork\Clockwork;
use Symfony\Component\HttpFoundation\Request;

class Middleware
{
    public function __invoke(Request $request, callable $next)
    {
        if(strpos($request->getPathInfo(), '__clockwork') !== false){
            return $next($request);
        }

        $clockwork = app(Clockwork::class);
        $response = $next($request);

        $dataSource = app(DataSource::class);
        $dataSource->setResponse($response);

        $response->headers->set('X-Clockwork-Id', $clockwork->getRequest()->id);
        $response->headers->set('X-Clockwork-Version', Clockwork::VERSION);
        $clockwork->resolveRequest();
        $clockwork->storeRequest();
        return $response;
    }
}