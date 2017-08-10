<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 24/11/2016
 * Time: 21:37
 */

namespace Ecfectus\Framework\Bootstrap;

use Ecfectus\Framework\Application;
use Ecfectus\Framework\Exceptions\Handler;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Symfony\Component\HttpFoundation\Request;

class HandleExceptions
{

    protected $app;

    public function bootstrap(Application $app)
    {
        $this->app = $app;

        error_reporting(-1);

        set_error_handler([$this, 'handleError']);

        set_exception_handler([$this, 'handleException']);

        register_shutdown_function([$this, 'handleShutdown']);

        if (env('APP_ENV') === 'production') {
            ini_set('display_errors', 'Off');
        }
    }

    public function handleError($level, $message, $file = '', $line = 0, $context = [])
    {
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
    }

    public function handleException($e)
    {
        if (! $e instanceof \Exception) {
            $e = new FatalThrowableError($e);
        }
        try {
            $this->getExceptionHandler()->report($e);
        } catch (\Exception $e) {
            //
        }
        if ($this->app->runningInConsole()) {
            $this->renderForConsole($e);
        } else {
            $this->renderHttpResponse($e);
        }
    }

    protected function renderForConsole(\Exception $e)
    {
        $this->getExceptionHandler()->renderForConsole(new ConsoleOutput, $e);
    }

    protected function renderHttpResponse(\Exception $e)
    {
        $this->getExceptionHandler()->render($this->app->get(Request::class), $e)->prepare($this->app->get(Request::class))->send();
    }

    public function handleShutdown()
    {
        if (! is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
            $this->handleException($this->fatalExceptionFromError($error, 0));
        }
    }

    protected function fatalExceptionFromError(array $error, $traceOffset = null)
    {
        return new FatalErrorException(
            $error['message'], $error['type'], 0, $error['file'], $error['line'], $traceOffset
        );
    }

    protected function isFatal($type)
    {
        return in_array($type, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE]);
    }

    protected function getExceptionHandler()
    {
        return $this->app->get(Handler::class);
    }

}