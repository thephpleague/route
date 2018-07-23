<?php

namespace League\Route\Test;

use League\Route\Route;
use League\Route\Test\Asset\Controller;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    /**
     * Asserts that the route can set and resolve an invokable class callable.
     *
     * @return void
     */
    public function testRouteSetsAndResolvesInvokableClassCallable()
    {
        $route = new Route;
        $callable = new Controller;

        $route->setCallable($callable);
        $this->assertTrue(is_callable($route->getCallable()));
    }

    /**
     * Asserts that the route can set and resolve a class method callable.
     *
     * @return void
     */
    public function testRouteSetsAndResolvesClassMethodCallable()
    {
        $route = new Route;
        $callable = [new Controller, 'action'];

        $route->setCallable($callable);
        $this->assertTrue(is_callable($route->getCallable()));
    }

    /**
     * Asserts that the route can set and resolve a named function callable.
     *
     * @return void
     */
    public function testRouteSetsAndResolvesNamedFunctionCallable()
    {
        $route = new Route;
        $callable = 'League\Route\Test\Asset\namedFunctionCallable';

        $route->setCallable($callable);
        $this->assertTrue(is_callable($route->getCallable()));
    }

    /**
     * Asserts that the route can set and resolve a class method callable via the container.
     *
     * @return void
     */
    public function testRouteSetsAndResolvesClassMethodCallableAsStringViaContainer()
    {
        $container = $this->getMock('League\Container\ImmutableContainerInterface');

        $container->expects($this->once())->method('has')->with($this->equalTo('League\Route\Test\Asset\Controller'))->will($this->returnValue(true));
        $container->expects($this->once())->method('get')->with($this->equalTo('League\Route\Test\Asset\Controller'))->will($this->returnValue(new Controller));

        $route = new Route;
        $route->setContainer($container);

        $callable = 'League\Route\Test\Asset\Controller::action';

        $route->setCallable($callable);
        $newCallable = $route->getCallable();

        $this->assertTrue(is_callable($newCallable));
        $this->assertTrue(is_array($newCallable));
        $this->assertCount(2, $newCallable);
        $this->assertInstanceOf('League\Route\Test\Asset\Controller', $newCallable[0]);
        $this->assertEquals('action', $newCallable[1]);
    }

    /**
     * Asserts that the route can set and resolve a class method callable without the container.
     *
     * @return void
     */
    public function testRouteSetsAndResolvesClassMethodCallableAsStringWithoutContainer()
    {
        $container = $this->getMock('League\Container\ImmutableContainerInterface');

        $container->expects($this->once())->method('has')->with($this->equalTo('League\Route\Test\Asset\Controller'))->will($this->returnValue(false));

        $route = new Route;
        $route->setContainer($container);

        $callable = 'League\Route\Test\Asset\Controller::action';

        $route->setCallable($callable);
        $newCallable = $route->getCallable();

        $this->assertTrue(is_callable($newCallable));
        $this->assertTrue(is_array($newCallable));
        $this->assertCount(2, $newCallable);
        $this->assertInstanceOf('League\Route\Test\Asset\Controller', $newCallable[0]);
        $this->assertEquals('action', $newCallable[1]);
    }

    /**
     * Asserts that the route throws an exception when trying to set and resolve a non callable.
     *
     * @return void
     */
    public function testRouteThrowsExceptionWhenSettingAndResolvingNonCallable()
    {
        $this->setExpectedException('InvalidArgumentException');

        $route = new Route;
        $callable = new \stdClass;

        $route->setCallable($callable);
        $route->getCallable();
    }

    /**
     * Asserts that the route can set and get all properties.
     *
     * @return void
     */
    public function testRouteCanSetAndGetAllProperties()
    {
        $route = new Route;

        $group = $this->getMockBuilder('League\Route\RouteGroup')->disableOriginalConstructor()->getMock();
        $this->assertSame($group, $route->setParentGroup($group)->getParentGroup());

        $path = '/something';
        $this->assertSame('/something', $route->setPath($path)->getPath());

        $methods = ['get', 'post'];
        $this->assertSame($methods, $route->setMethods($methods)->getMethods());

        $name = 'a.name';
        $this->assertSame($name, $route->setName($name)->getName());

        $scheme = 'http';
        $this->assertSame($scheme, $route->setScheme($scheme)->getScheme());

        $host = 'example.com';
        $this->assertSame($host, $route->setHost($host)->getHost());

        $port = 8080;
        $this->assertSame($port, $route->setPort($port)->getPort());

        $middleware = new Controller;
        $route->middleware($middleware)->middleware($middleware);
        $this->assertSame([
            $middleware, $middleware
        ], $route->getMiddlewareStack());
    }

    /**
     * Asserts the route proxies to the strategy and builds the execution chain.
     *
     * @return void
     */
    public function testRouteProxiesToStrategyAndBuildsExecutionChain()
    {
        $route = new Route;
        $vars  = [];

        $callable = function () {};

        $strategy = $this->getMock('League\Route\Strategy\StrategyInterface');
        $strategy->expects($this->once())->method('getCallable')->with($this->equalTo($route), $this->equalTo($vars))->will($this->returnValue($callable));

        $route->setStrategy($strategy);

        $this->assertInstanceOf('League\Route\Middleware\ExecutionChain', $route->getExecutionChain($vars));
    }
}
