<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 26/11/2016
 * Time: 23:09
 */

namespace Ecfectus\Framework\Session\Http\Middleware;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class StartSessionMiddleware
{
    protected $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function __invoke(Request $request, callable $next)
    {
        $this->session->start();
        $request->setSession($this->session);
        return $next($request);
    }

}