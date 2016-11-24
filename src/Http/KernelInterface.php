<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 11/10/16
 * Time: 16:59
 */

namespace Ecfectus\Framework\Http;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface KernelInterface
{
    public function handle(Request $request) : Response;

    public function terminate(Request $request, Response $response);

}