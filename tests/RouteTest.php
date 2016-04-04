<?php

namespace League\Route\Test;

use League\Route\Route;

class RouteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that a route can set and get all properties.
     */
    public function testRouteSetsAndGetsProperties()
    {
        $route = new Route;

        $strategy = $this->getMock('League\Route\Strategy\StrategyInterface');
        $group    = $this->getMockBuilder('League\Route\RouteGroup')->disableOriginalConstructor()->getMock();

        $callable = function () {};
        $host     = 'example.com';
        $methods  = ['GET', 'POST'];
        $name     = 'example';
        $path     = '/example';
        $scheme   = 'https';

        $route->setCallable($callable);
        $this->assertSame($callable, $route->getCallable());

        $route->setHost($host);
        $this->assertSame($host, $route->getHost());

        $route->setMethods($methods);
        $this->assertSame($methods, $route->getMethods());

        $route->setName($name);
        $this->assertSame($name, $route->getName());

        $route->setPath($path);
        $this->assertSame($path, $route->getPath());

        $route->setScheme($scheme);
        $this->assertSame($scheme, $route->getScheme());

        $route->setStrategy($strategy);
        $this->assertSame($strategy, $route->getStrategy());

        $route->setparentGroup($group);
        $this->assertSame($group, $route->getParentGroup());
    }

    /**
     * Asserts that a route can dispatch a closure to the correct strategy.
     */
    public function testRouteCanDispatchClosureToCorrectStrategy()
    {
        $route = new Route;

        $strategy = $this->getMock('League\Route\Strategy\StrategyInterface');
        $request  = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');
        $callable = function () {};

        $strategy->expects($this->once())->method('dispatch')->with($callable, [])->will($this->returnValue($response));

        $route->setStrategy($strategy);
        $route->setCallable($callable);

        $actual = $route->dispatch($request, $response, []);

        $this->assertSame($response, $actual);
    }

    /**
     * Asserts that a route can dispatch a invokable class to the correct strategy.
     */
    public function testRouteCanDispatchInvokableClassToCorrectStrategy()
    {
        $route = new Route;

        $strategy = $this->getMock('League\Route\Strategy\StrategyInterface');
        $request  = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');
        $runner   = $this->getMock('League\Route\Middleware\Runner');
        $callable = new Asset\InvokableController;

        $runner->expects($this->once())->method('before')->with($this->equalTo($callable));
        $runner->expects($this->once())->method('after')->with($this->equalTo($callable));

        $strategy->expects($this->once())->method('dispatch')->with($callable, [])->will($this->returnValue($response));

        $route->setMiddlewareRunner($runner);

        $route->setStrategy($strategy);
        $route->setCallable($callable);

        $route->before($callable);
        $route->after($callable);

        $actual = $route->dispatch($request, $response, []);

        $this->assertSame($response, $actual);
    }

    /**
     * Asserts that a route can dispatch a array based callable with a class instance to the correct strategy.
     */
    public function testRouteCanDispatchArrayBasedCallableWithInstanceToCorrectStrategy()
    {
        $route = new Route;

        $container = $this->getMock('Interop\Container\ContainerInterface');
        $strategy  = $this->getMock('League\Route\Strategy\StrategyInterface');
        $request   = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response  = $this->getMock('Psr\Http\Message\ResponseInterface');
        $instance  = new Asset\InvokableController;
        $callable  = [$instance, '__invoke'];

        $strategy->expects($this->once())->method('dispatch')->with($callable, [])->will($this->returnValue($response));

        $route->setContainer($container);
        $route->setStrategy($strategy);
        $route->setCallable($callable);

        $actual = $route->dispatch($request, $response, []);

        $this->assertSame($response, $actual);
    }

    /**
     * Asserts that a route can dispatch a array based callable with a class name to the correct strategy.
     */
    public function testRouteCanDispatchArrayBasedCallableWithNameToCorrectStrategy()
    {
        $route = new Route;

        $container = $this->getMock('Interop\Container\ContainerInterface');
        $strategy  = $this->getMock('League\Route\Strategy\StrategyInterface');
        $request   = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response  = $this->getMock('Psr\Http\Message\ResponseInterface');
        $callable  = ['League\Route\Test\Asset\InvokableController', '__invoke'];
        $instance  = new Asset\InvokableController;

        $container->expects($this->once())->method('has')->with('League\Route\Test\Asset\InvokableController')->will($this->returnValue(true));
        $container->expects($this->once())->method('get')->with('League\Route\Test\Asset\InvokableController')->will($this->returnValue($instance));
        $strategy->expects($this->once())->method('dispatch')->with([$instance, '__invoke'], [])->will($this->returnValue($response));

        $route->setContainer($container);
        $route->setStrategy($strategy);
        $route->setCallable($callable);

        $actual = $route->dispatch($request, $response, []);

        $this->assertSame($response, $actual);
    }

    /**
     * Asserts that a route can dispatch a string based callable to the correct strategy.
     */
    public function testRouteCanDispatchStringBasedCallableToCorrectStrategy()
    {
        $route = new Route;

        $container = $this->getMock('Interop\Container\ContainerInterface');
        $strategy  = $this->getMock('League\Route\Strategy\StrategyInterface');
        $request   = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response  = $this->getMock('Psr\Http\Message\ResponseInterface');
        $callable  = 'League\Route\Test\Asset\InvokableController::__invoke';
        $instance  = new Asset\InvokableController;

        $container->expects($this->once())->method('has')->with('League\Route\Test\Asset\InvokableController')->will($this->returnValue(true));
        $container->expects($this->once())->method('get')->with('League\Route\Test\Asset\InvokableController')->will($this->returnValue($instance));
        $strategy->expects($this->once())->method('dispatch')->with([$instance, '__invoke'], [])->will($this->returnValue($response));

        $route->setContainer($container);
        $route->setStrategy($strategy);
        $route->setCallable($callable);

        $actual = $route->dispatch($request, $response, []);

        $this->assertSame($response, $actual);
    }

    /**
     * Asserts that a route can dispatch a named function to the correct strategy.
     */
    public function testRouteCanDispatchNamedFunctionToCorrectStrategy()
    {
        $route = new Route;

        $strategy = $this->getMock('League\Route\Strategy\StrategyInterface');
        $request  = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');
        $callable = 'League\Route\Test\Asset\namedFunctionController';

        $strategy->expects($this->once())->method('dispatch')->with($callable, [])->will($this->returnValue($response));

        $route->setStrategy($strategy);
        $route->setCallable($callable);

        $actual = $route->dispatch($request, $response, []);

        $this->assertSame($response, $actual);
    }

    /**
     * Ensure an exception is thrown when providing an invalid class method.
     */
    public function testRouteThrowsExceptionWhenClassMethodIsNotCallable()
    {
        $this->setExpectedException(
            'RuntimeException', 'Invalid class method provided for: League\Route\Test\Asset\TestController::invalid'
        );

        $route = new Route;

        $container = $this->getMock('Interop\Container\ContainerInterface');
        $request   = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response  = $this->getMock('Psr\Http\Message\ResponseInterface');
        $callable  = 'League\Route\Test\Asset\TestController::invalid';
        $instance  = new Asset\TestController;

        $route->setContainer($container);
        $route->setCallable($callable);

        $route->dispatch($request, $response, []);
    }
}
