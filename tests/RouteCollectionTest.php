<?php

namespace League\Route\Test;

use League\Route\RouteCollection;

class RouteCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the collection can map and return a route object.
     *
     * @return void
     */
    public function testCollectionMapsAndReturnsRoute()
    {
        $collection = new RouteCollection;
        $path       = '/something';
        $callable   = function () {};

        foreach ([
            'get', 'post', 'put', 'patch', 'delete', 'head', 'options'
        ] as $method) {
            $route = $collection->map($method, $path, $callable);

            $this->assertInstanceOf('League\Route\Route', $route);
            $this->assertSame([$method], $route->getMethods());
            $this->assertSame($path, $route->getPath());
            $this->assertSame($callable, $route->getCallable());
        }
    }

    /**
     * Asserts that the collection can map and return a route group object.
     *
     * @return void
     */
    public function testCollectionMapsAndReturnsGroup()
    {
        $collection = new RouteCollection;
        $prefix     = '/something';
        $callable   = function () {};

        $group = $collection->group($prefix, $callable);

        $this->assertInstanceOf('League\Route\RouteGroup', $group);
    }

    /**
     * Asserts that the collection can set a named route and retrieve it by name.
     *
     * @return void
     */
    public function testCollectionCanSetAndGetNamedRoute()
    {
        $collection = new RouteCollection;
        $name       = 'route';

        $expected = $collection->map('get', '/something', function () {})->setName($name);
        $actual   = $collection->getNamedRoute($name);

        $this->assertSame($expected, $actual);
    }

    /**
     * Asserts that the collection can set a named route via a group and retrieve it by name.
     *
     * @return void
     */
    public function testCollectionCanSetViaGroupAndGetNamedRoute()
    {
        $collection = new RouteCollection;
        $name       = 'route';

        $collection->group('/prefix', function ($collection) use ($name) {
            $collection->map('get', '/something', function () {})->setName($name);
        });

        $route = $collection->getNamedRoute($name);
        $this->assertInstanceOf('League\Route\Route', $route);
    }

    /**
     * Asserts that an exception is thrown when trying to get a named route that does not exist.
     *
     * @return void
     */
    public function testCollectionThrowsExceptionWhenAttemptingToGetNamedRouteThstDoesNotExist()
    {
        $this->setExpectedException('InvalidArgumentException');

        (new RouteCollection)->getNamedRoute('umm');
    }

    /**
     * Asserts that appropriately configured regex strings are added to patternMatchers.
     *
     * @return void
     */
    public function testNewPatternMatchesCanBeAddedAtRuntime()
    {
        $collection = new RouteCollection;
        $collection->addPatternMatcher('mockMatcher', '[a-zA-Z]');
        $matchers = $this->getObjectAttribute($collection, 'patternMatchers');

        $this->assertArrayHasKey('/{(.+?):mockMatcher}/', $matchers);
        $this->assertEquals('{$1:[a-zA-Z]}', $matchers['/{(.+?):mockMatcher}/']);
    }

    /**
     * Asserts that the collection can prep routes and build a dispatcher.
     *
     * @return void
     */
    public function testCollectionPrepsRoutesAndBuildsDispatcher()
    {
        $collection = new RouteCollection;
        $request    = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $uri        = $this->getMock('Psr\Http\Message\UriInterface');

        $uri->expects($this->exactly(2))->method('getScheme')->will($this->returnValue('https'));
        $uri->expects($this->exactly(2))->method('getHost')->will($this->returnValue('something.com'));
        $request->expects($this->exactly(4))->method('getUri')->will($this->returnValue($uri));

        $collection->map('get', '/something', function () {})->setScheme('http');
        $collection->map('post', '/something', function () {})->setHost('example.com');
        $collection->map('get', '/something-else', function () {})->setScheme('https')->setHost('something.com');

        $dispatcher = $collection->getDispatcher($request);

        $this->assertInstanceOf('League\Route\Dispatcher', $dispatcher);
    }

    /**
     * Asserts that collection can get dispatcher multiple times.
     *
     * @return void
     */
    public function testCollectionGetDispatcherMultipleTimes()
    {
        $collection = new RouteCollection;
        $request    = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $collection->map('get', '/something', function () {});

        $collection->getDispatcher($request);
        $collection->getDispatcher($request);
    }
}
