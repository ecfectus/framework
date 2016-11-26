<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 11/10/16
 * Time: 14:32
 */

namespace Ecfectus\Framework\Test;

use Ecfectus\Container\ContainerInterface;
use Ecfectus\Container\ReflectionContainer;
use Ecfectus\Container\ServiceProviderContainer;
use Ecfectus\Events\Dispatcher;
use Ecfectus\Events\DispatcherInterface;
use Ecfectus\Framework\Application;
use Ecfectus\Framework\Bootstrap\Events\AfterBootstrap;
use Ecfectus\Framework\Bootstrap\Events\BeforeBootstrap;
use Ecfectus\Framework\Config\Repository;
use Ecfectus\Framework\Config\RepositoryInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;


class ApplicationTest extends TestCase
{

    public function testConstructor()
    {
        $app = new Application(realpath(__DIR__ . '/'));

        $this->assertInstanceOf(ContainerInterface::class, $app);

    }

    public function testBaseSetup()
    {
        $app = new Application(realpath(__DIR__ . '/'));

        $this->assertTrue($app->has(ContainerInterface::class));
        $this->assertTrue($app->has(Application::class));
        $this->assertTrue($app->has('app'));
        $this->assertTrue($app->has(ServiceProviderContainer::class));
        $this->assertTrue($app->has(ReflectionContainer::class));
        $this->assertTrue($app->has('path'));
        $this->assertTrue($app->has('path.config'));
        $this->assertTrue($app->has(RepositoryInterface::class));
        $this->assertTrue($app->has(DispatcherInterface::class));

        $this->assertSame($app, $app->get(ContainerInterface::class));
        $this->assertSame($app, $app->get(Application::class));
        $this->assertSame($app, $app->get('app'));

        $this->assertEquals(realpath(__DIR__ . '/'), $app->get('path'));
        $this->assertEquals(realpath(__DIR__ . '/config'), $app->get('path.config'));

        $this->assertInstanceOf(Dispatcher::class, $app->get(DispatcherInterface::class));
        $this->assertInstanceOf(Repository::class, $app->get(RepositoryInterface::class));

    }

    public function testBootstrapFiresEvents()
    {
        $app = new Application(realpath(__DIR__ . '/'));

        $dispatcher = $this->prophesize(Dispatcher::class);

        $dispatcher->listen(Argument::type('string'), Argument::any())->shouldBeCalled();

        $dispatcher->fire(new BeforeBootstrap($app))->shouldBeCalledTimes(1);

        $dispatcher->fire(new AfterBootstrap($app))->shouldBeCalledTimes(1);

        $app->bind(DispatcherInterface::class, $dispatcher->reveal());

        $app->bootstrap();

        $app->bootstrap();
    }

    public function testBootstrapAltersState()
    {
        $app = new Application(realpath(__DIR__ . '/'));

        $this->assertFalse($app->hasBeenBootstrapped());

        $app->bootstrap();

        $this->assertTrue($app->hasBeenBootstrapped());
    }

    public function testResolveMethod()
    {
        $app = new Application(realpath(__DIR__ . '/'));

        $app->bootstrap();

        $app->get(RepositoryInterface::class)->set('app.test', true);

        $cb = $app->resolve('Ecfectus\Framework\Config\RepositoryInterface@get');

        $this->assertTrue($cb('app.test'));

        $cb = $app->resolve(function($value){return 'return ' . $value;});

        $this->assertEquals('return value', $cb('value'));
    }

}