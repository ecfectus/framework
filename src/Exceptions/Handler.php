<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 10/08/2017
 * Time: 12:40
 */

namespace Ecfectus\Framework\Exceptions;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Handler
{

    public function report(\Throwable $e)
    {

    }

    public function renderForConsole(ConsoleOutput $output, \Throwable $e)
    {
        //@TODO
    }

    public function render(Request $request, \Throwable $e)
    {
        return (new Response())
            ->setStatusCode(500)
            ->setContent($e->getMessage())
            ->prepare($request);
    }

}