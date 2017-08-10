<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 10/08/2017
 * Time: 12:40
 */

namespace Ecfectus\Framework\Exceptions;

use Ecfectus\Router\MethodNotAllowedException;
use Ecfectus\Router\NotFoundException;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

class Handler
{

    protected $dontReport = [
        MethodNotAllowedException::class,
        NotFoundException::class
    ];

    protected $reported = [];

    public function report(\Throwable $e)
    {
        if($this->shouldReport($e)){
            //report it here
            $this->reported[] = $e;
        }
    }

    protected function shouldReport(\Throwable $e)
    {
        return count(array_filter($this->dontReport, function($type) use ($e){
            return $e instanceof $type;
        })) === 0;
    }

    public function renderForConsole(ConsoleOutput $output, \Throwable $e)
    {
        $whoops = app(Run::class);
        $whoops->pushHandler(new PlainTextHandler());
        $whoops->handleException($e);
    }

    public function render(Request $request, \Throwable $e, $statusCode = 500)
    {
        $whoops = app(Run::class);

        $handler = app(PrettyPageHandler::class);

        $handler->addDataTable('Reported', $this->reported);

        $whoops->pushHandler($handler);

        if (in_array('application/json', $request->getAcceptableContentTypes())) {
            $whoops->pushHandler(new JsonResponseHandler());
        }

        return (new Response())
            ->setStatusCode($statusCode)
            ->setContent($whoops->handleException($e));
    }

}