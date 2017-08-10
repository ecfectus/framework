<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 10/08/2017
 * Time: 16:07
 */

namespace Ecfectus\Framework\Dev\Clockwork;

use Clockwork\Request\Request;
use Clockwork\Request\Timeline;
use Ecfectus\Events\DispatcherInterface;
use Ecfectus\Events\Event;
use Ecfectus\Framework\Application;
use Ecfectus\Framework\Bootstrap\Events\AfterBootstrap;
use Clockwork\DataSource\DataSource as BaseDataSource;
use Ecfectus\Framework\Http\Events\PipelineFinished;
use Ecfectus\Framework\Http\Events\PipelineStarted;
use Ecfectus\Framework\Http\Events\RouteMatched;
use Ecfectus\Framework\Http\Events\RouteMatching;
use Ecfectus\Framework\Http\Events\RouteMethodNotAllowed;
use Ecfectus\Framework\Http\Events\RouteNotFound;
use Ecfectus\Framework\Http\Events\Terminate;
use Ecfectus\Router\RouterInterface;
use Symfony\Component\HttpFoundation\Response;

class DataSource extends BaseDataSource
{

    private $app;

    protected $response;

    protected $timeline;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->timeline = new Timeline();

        $this->listenToEvents();
    }

    public function resolve(Request $request)
    {
        $request->method         = $this->getRequestMethod();
        $request->uri            = $this->getRequestUri();
        $request->controller     = $this->getController();
        $request->headers        = $this->getRequestHeaders();
        $request->responseStatus = $this->getResponseStatus();
        $request->routes         = $this->getRoutes();
        $request->sessionData    = $this->getSessionData();
        $request->timelineData = array_merge($request->timelineData, $this->timeline->finalize($request->time));
        return $request;
    }

    /**
     * Set a custom response instance
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Hook up callbacks for various Laravel events, providing information for timeline and log entries
     */
    public function listenToEvents()
    {
        $timeline = $this->timeline;
        $timeline->startEvent('total', 'Total execution time.');
        $timeline->startEvent('initialisation', 'Application initialisation.');
        $timeline->endEvent('initialisation');
        $timeline->startEvent('boot', 'Framework booting.');
        $timeline->startEvent('run', 'Framework running.');

        $events = $this->app->get(DispatcherInterface::class);

        $events->listen('*', function(Event $e) use($timeline){
            $timeline->startEvent(strtolower(get_class($e)), get_class($e));
        }, 100000000);
        $events->listen('*', function(Event $e) use($timeline){
            $timeline->endEvent(strtolower(get_class($e)));
        }, -100000000);

        $events->listen(AfterBootstrap::class, function(Event $e) use($timeline){
            $timeline->endEvent('boot');
        });

        // Routing
        $events->listen(RouteMatching::class, function(Event $e) use($timeline){
            $timeline->startEvent('routing', 'Routing Request');
        });
        $events->listen(RouteMatched::class, function(Event $e) use($timeline){
            $timeline->endEvent('routing');
        });
        $events->listen(RouteNotFound::class, function(Event $e) use($timeline){
            $timeline->endEvent('routing');
        });
        $events->listen(RouteMethodNotAllowed::class, function(Event $e) use($timeline){
            $timeline->endEvent('routing');
        });

        // Pipeline
        $events->listen(PipelineStarted::class, function(Event $e) use($timeline){
            $timeline->startEvent('pipeline', 'Piping Request Through Middleware');
        });
        $events->listen(PipelineFinished::class, function(Event $e) use($timeline){
            $timeline->endEvent('pipeline');
        });

        $events->listen(Terminate::class, function(Event $e) use($timeline){
            $timeline->endEvent('run');
            $timeline->endEvent('total');
        });
    }

    protected function getController()
    {
        $route = $this->app->get('request')->attributes->get('route', null);
        if($route){
            return $route->getName() ?? 'Unnamed Route';
        }
    }

    /**
     * Return request headers
     */
    protected function getRequestHeaders()
    {
        return $this->app->get('request')->headers->all();
    }
    /**
     * Return request method
     */
    protected function getRequestMethod()
    {
        return $this->app->get('request')->getMethod();
    }
    /**
     * Return request URI
     */
    protected function getRequestUri()
    {
        return $this->app->get('request')->getRequestUri();
    }
    /**
     * Return response status code
     */
    protected function getResponseStatus()
    {
        return $this->response->getStatusCode();
    }

    protected function getRoutes()
    {
        $routes = $this->app->get(RouterInterface::class)->getRoutes();

        $data = [];

        foreach($routes as $route){
            $data[] = [
                'method' => implode(', ', $route->getMethods()),
                'uri'    => '/'.$route->getPath(),
                'name'   => $route->getName(),
                'action' => is_string($route->getHandler()) ? $route->getHandler() : 'Closure',
                'before' => implode(', ', $route->getMiddleware())
            ];
        }

        return $data;
    }

    /**
     * Return session data (replace unserializable items, attempt to remove passwords)
     */
    protected function getSessionData()
    {
        return $this->app->get('request')->getSession()->all();
    }
}