<?php
namespace Ecfectus\Test\Http;

use Ecfectus\Container\Container;
use Ecfectus\Container\ReflectionContainer;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Ecfectus\Http\Runner;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class MiddlewareRunnerTest extends \PHPUnit_Framework_TestCase
{
    public function testRunnerInitialises()
    {
        $runner = new Runner();

        $this->assertInstanceOf(Runner::class, $runner);
    }

    public function testRunnerCanConstructWithMiddlewares()
    {
        $runner = new Runner([
            function (RequestInterface $request, ResponseInterface $response, $next) {
                return $response;
            },
            'aclassstring'
        ]);

        $this->assertCount(2, $runner->getMiddlewares());
    }

    public function testAddingContainerToRunner()
    {
        $runner = new Runner();

        $runner->setContainer(new Container());

    }

    public function testContainerResolvesMiddlewares(){
        
        $container = new Container();
        $container->delegate(new ReflectionContainer());
        
        $runner = new Runner();
        $runner->setContainer($container);
        $runner->addMiddleware(TestClass::class);

        $this->assertCount(1, $runner->getMiddlewares());

        $response = $runner(ServerRequestFactory::fromGlobals(), new Response());

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testRunnerResolvesMiddlewares(){

        $runner = new Runner();
        $runner->addMiddleware(TestClassWithoutDependencies::class);

        $this->assertCount(1, $runner->getMiddlewares());

        $response = $runner(ServerRequestFactory::fromGlobals(), new Response());

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testEmptyMiddlewareListDoesntFail(){
        $runner = new Runner();
        $response = $runner(ServerRequestFactory::fromGlobals(), new Response());

        $this->assertInstanceOf(Response::class, $response);
    }
}
