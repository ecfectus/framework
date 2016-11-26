<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 11/10/16
 * Time: 18:27
 */

namespace Ecfectus\Framework\Test\Http;

use Ecfectus\Framework\Application;
use Ecfectus\Framework\Http\KernelInterface;
use Ecfectus\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

class Controller{

    public function fromResponse(Request $request){
        $response = (new Response())->setContent('from response func');
        return $response;
    }

    public function fromString(Request $request){
        return 'from string func';
    }

    public function fromArray(Request $request){
        return ['zero' => '1', 'one' => '2','two' => '3','three' => '4'];
    }

    public function fromObject(Request $request){
        return (object) ['zero' => '1', 'one' => '2','two' => '3','three' => '4'];
    }
}

class KernelTest extends TestCase
{

    protected $app;

    public function setUp()
    {
        parent::setUp();
        $this->app = new Application(realpath(__DIR__ . '/../'));
        $this->app->bootstrap();
    }

    public function testConstructor()
    {
        $kernel = $this->app->get(KernelInterface::class);

        $this->assertInstanceOf(KernelInterface::class, $kernel);
    }

    public function testSendThroughPipeline()
    {
        $kernel = $this->app->get(KernelInterface::class);

        $this->assertInstanceOf(Response::class, $kernel->handle($this->app->get(Request::class)));
    }

    public function testSendThroughPipeline404()
    {
        $kernel = $this->app->get(KernelInterface::class);

        $request = Request::create(
            '/',
            'GET'
        );

        $response = $kernel->handle($request);

        $this->assertEquals(404, $response->getStatusCode());

    }

    public function testSendThroughPipelineWithMiddleware()
    {

        $kernel = $this->app->get(KernelInterface::class);

        $kernel->globalMiddleware[] = function(Request $request, callable $next){
            $response = $next($request);
            return $response->setStatusCode(301);
        };

        $kernel->globalMiddleware[] = function(Request $request, callable $next){
            $response = $next($request);
            return $response->setContent('test middleware');
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
        $kernel = $this->app->get(KernelInterface::class);

        $router = $this->app->get(RouterInterface::class);

        $route = $router->get('/')->setHandler(function(Request $request){
            $response = (new Response())->setContent('testing route');
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
        $kernel = $this->app->get(KernelInterface::class);

        $router = $this->app->get(RouterInterface::class);

        $router->get('/object')->setHandler(function(Request $request){
            return (object) ['zero' => '1', 'one' => '2','two' => '3','three' => '4'];
        });

        $router->get('/array')->setHandler(function(Request $request){
            return ['zero' => '1', 'one' => '2','two' => '3','three' => '4'];
        });

        $router->get('/int')->setHandler(function(Request $request){
            return 1;
        });

        $router->get('/bool')->setHandler(function(Request $request){
            return true;
        });

        $router->get('/float')->setHandler(function(Request $request){
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
        $kernel = $this->app->get(KernelInterface::class);

        $router = $this->app->get(RouterInterface::class);

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
        $kernel = $this->app->get(KernelInterface::class);

        $router = $this->app->get(RouterInterface::class);

        $route = $router->get('/')->setHandler(function(Request $request){
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


    public function testSessionIsAdded()
    {
        $this->app->bind(SessionStorageInterface::class, new MockArraySessionStorage());

        $kernel = $this->app->get(KernelInterface::class);

        $router = $this->app->get(RouterInterface::class);

        $route = $router->get('/')->setHandler(function(Request $request){
            return $request->hasSession();
        })->setMiddleware([
            'session'
        ]);

        $request = Request::create(
            '/',
            'GET'
        );

        $response = $kernel->handle($request);

        $this->assertEquals('1', $response->getContent());
    }

    public function testSessionIsStarted()
    {
        $this->app->bind(SessionStorageInterface::class, new MockArraySessionStorage());

        $kernel = $this->app->get(KernelInterface::class);

        $router = $this->app->get(RouterInterface::class);

        $route = $router->get('/')->setHandler(function(Request $request){
            return $request->getSession()->isStarted();
        })->setMiddleware([
            'session'
        ]);

        $request = Request::create(
            '/',
            'GET'
        );

        $response = $kernel->handle($request);

        $this->assertEquals('1', $response->getContent());
    }

    public function testSessionContainsData()
    {
        $this->app->bind(SessionStorageInterface::class, new MockArraySessionStorage());

        $kernel = $this->app->get(KernelInterface::class);

        $router = $this->app->get(RouterInterface::class);

        $route = $router->get('/')->setHandler(function(Request $request){
            return $request->getSession()->get('test');
        })->setMiddleware([
            'session',
            function(Request $request, callable $next){
                $request->getSession()->set('test', 'value');
                return $next($request);
            }
        ]);

        $request = Request::create(
            '/',
            'GET'
        );

        $response = $kernel->handle($request);

        $this->assertEquals('value', $response->getContent());
    }

}