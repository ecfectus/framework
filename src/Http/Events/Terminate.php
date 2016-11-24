<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 24/11/2016
 * Time: 22:11
 */

namespace Ecfectus\Framework\Http\Events;


use Ecfectus\Events\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Terminate extends Event
{

    public $response;

    public $request;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
}