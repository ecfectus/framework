<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 11/10/16
 * Time: 18:27
 */

namespace Ecfectus\Framework\Test\Http;


use Ecfectus\Events\DispatcherInterface;
use Ecfectus\Events\Event;
use Ecfectus\Framework\Application;
use Ecfectus\Framework\Bootstrap\Events\AfterBootstrap;
use Ecfectus\Framework\Bootstrap\Events\BeforeBootstrap;
use Ecfectus\Framework\Http\Events\RouteMatched;
use Ecfectus\Framework\Http\KernelInterface;
use Ecfectus\Router\Route;
use Ecfectus\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Controller{

    public function fromResponse(Request $request, Response $response){
        $response->setContent('from response func');
        return $response;
    }

    public function fromString(Request $request, Response $response){
        return 'from string func';
    }

    public function fromArray(Request $request, Response $response){
        return ['zero' => '1', 'one' => '2','two' => '3','three' => '4'];
    }

    public function fromObject(Request $request, Response $response){
        return (object) ['zero' => '1', 'one' => '2','two' => '3','three' => '4'];
    }
}

class KernelTest extends TestCase
{

    public function testConstructor()
    {
        $app = new Application(realpath(__DIR__ . '/../'));

        $app->bootstrap();

        $kernel = $app->get(KernelInterface::class);

        $this->assertInstanceOf(KernelInterface::class, $kernel);
    }

    public function testSendThroughPipeline()
    {
        $app = new Application(realpath(__DIR__ . '/../'));

        $app->bootstrap();

        $kernel = $app->get(KernelInterface::class);

        $this->assertInstanceOf(Response::class, $kernel->handle($app->get(Request::class)));
    }

    public function testSendThroughPipelineWithMiddleware()
    {
        $app = new Application(realpath(__DIR__ . '/../'));

        $app->bootstrap();

        $kernel = $app->get(KernelInterface::class);

        $request = Request::create(
            '/',
            'GET'
        );

        $response = $kernel->handle($request);

        $this->assertEquals(404, $response->getStatusCode());


        $kernel->globalMiddleware[] = function(Request $request, Response $response, callable $next){
            $response->setStatusCode(301);
            return $next($request, $response);
        };

        $kernel->globalMiddleware[] = function(Request $request, Response $response, callable $next){
            $response->setContent('test middleware');
            return $next($request, $response);
        };

        $request = Request::create(
            '/',
            'GET'
        );

        $response = $kernel->handle($request);

        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('test middleware', $response->getContent());
    }

    public function testRouteGetsMatchedThroughRouter()
    {
        $app = new Application(realpath(__DIR__ . '/../'));

        $app->bootstrap();

        $kernel = $app->get(KernelInterface::class);

        $router = $app->get(RouterInterface::class);

        $route = $router->get('/')->setHandler(function(Request $request, Response $response){
            $response->setContent('testing route');
            return $response;
        });

        $request = Request::create(
            '/',
            'GET'
        );

        $response = $kernel->handle($request);

        $this->assertEquals('testing route', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());

        $request = Request::create(
            '/',
            'POST'
        );

        $response = $kernel->handle($request);

        $this->assertEquals('HEAD, GET', $response->headers->get('ALLOW'));
        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testRouteHandlerResponseGetsConvertedFromArray()
    {
        $app = new Application(realpath(__DIR__ . '/../'));

        $app->bootstrap();

        $kernel = $app->get(KernelInterface::class);

        $router = $app->get(RouterInterface::class);

        $router->get('/object')->setHandler(function(Request $request, Response $response){
            return (object) ['zero' => '1', 'one' => '2','two' => '3','three' => '4'];
        });

        $router->get('/array')->setHandler(function(Request $request, Response $response){
            return ['zero' => '1', 'one' => '2','two' => '3','three' => '4'];
        });

        $router->get('/int')->setHandler(function(Request $request, Response $response){
            return 1;
        });

        $router->get('/bool')->setHandler(function(Request $request, Response $response){
            return true;
        });

        $router->get('/float')->setHandler(function(Request $request, Response $response){
            return 0.5;
        });

        $response = $kernel->handle(Request::create('/object', 'GET'));
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals('{"zero":"1","one":"2","two":"3","three":"4"}', $response->getContent());

        $response = $kernel->handle(Request::create('/array', 'GET'));
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals('{"zero":"1","one":"2","two":"3","three":"4"}', $response->getContent());

        $response = $kernel->handle(Request::create('/int', 'GET'));
        $this->assertEquals('1', $response->getContent());

        $response = $kernel->handle(Request::create('/bool', 'GET'));
        $this->assertEquals('1', $response->getContent());

        $response = $kernel->handle(Request::create('/float', 'GET'));
        $this->assertEquals('0.5', $response->getContent());
    }

    public function testRouteHandlerFromController()
    {
        $app = new Application(realpath(__DIR__ . '/../'));

        $app->bootstrap();

        $kernel = $app->get(KernelInterface::class);

        $router = $app->get(RouterInterface::class);

        $router->get('/response')->setHandler('Ecfectus\Framework\Test\Http\Controller@fromResponse');
        $router->get('/string')->setHandler('Ecfectus\Framework\Test\Http\Controller@fromString');
        $router->get('/array')->setHandler('Ecfectus\Framework\Test\Http\Controller@fromarray');
        $router->get('/object')->setHandler('Ecfectus\Framework\Test\Http\Controller@fromObject');

        $response = $kernel->handle(Request::create('/response', 'GET'));
        $this->assertEquals('from response func', $response->getContent());

        $response = $kernel->handle(Request::create('/string', 'GET'));
        $this->assertEquals('from string func', $response->getContent());

        $response = $kernel->handle(Request::create('/array', 'GET'));
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals('{"zero":"1","one":"2","two":"3","three":"4"}', $response->getContent());

        $response = $kernel->handle(Request::create('/object', 'GET'));
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals('{"zero":"1","one":"2","two":"3","three":"4"}', $response->getContent());
    }


    public function testExceptionGetsCaught()
    {
        $app = new Application(realpath(__DIR__ . '/../'));

        $app->bootstrap();

        $kernel = $app->get(KernelInterface::class);

        $router = $app->get(RouterInterface::class);

        $route = $router->get('/')->setHandler(function(Request $request, Response $response){
            throw new \Exception('whoops!');
        });

        $request = Request::create(
            '/',
            'GET'
        );

        $response = $kernel->handle($request);

        $this->assertEquals('whoops!', $response->getContent());
        $this->assertEquals(500, $response->getStatusCode());
    }

}