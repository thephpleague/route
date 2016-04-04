<?php

namespace League\Route\Test;

use League\Route\RouteCollection;

class RouteCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the collection maps and stores a route object.
     */
    public function testCollectionMapsAndStoresRoute()
    {
        $router = new RouteCollection;

        $router->get('get/something', 'handler')->setName('get');
        $router->post('/post/something', 'handler')->setName('post');
        $router->put('put/something', 'handler')->setName('put');
        $router->patch('/patch/something', 'handler')->setName('patch');
        $router->delete('delete/something', 'handler')->setName('delete');
        $router->head('/head/something', 'handler')->setName('head');
        $router->options('options/something', 'handler')->setName('options');

        foreach (['get', 'post', 'put', 'patch', 'delete', 'head', 'options'] as $method) {
            $route = $router->getNamedRoute($method);
            $this->assertInstanceOf('League\Route\Route', $route);

            $this->assertSame("/${method}/something", $route->getPath());
            $this->assertSame('handler', $route->getCallable());
        }
    }

    /**
     * Asserts that the group method builds a group and returns it for any fluent manipulation.
     */
    public function testCollectionSetsAndReturnsGroup()
    {
        $router = new RouteCollection;
        $callable = function () {};

        $group = $router->group('/prefix', function ($collection) {
            $this->assertInstanceOf('League\Route\RouteGroup', $collection);

            $collection->get('get/something', 'handler')->setName('get');
            $collection->post('/post/something', 'handler')->setName('post');
            $collection->put('put/something', 'handler')->setName('put');
            $collection->patch('/patch/something', 'handler')->setName('patch');
            $collection->delete('delete/something', 'handler')->setName('delete');
            $collection->head('/head/something', 'handler')->setName('head');
            $collection->options('options/something', 'handler')->setName('options');
        })->setHost('example.com')->setScheme('http')->before($callable)->after($callable);

        $this->assertInstanceOf('League\Route\RouteGroup', $group);

        $group();

        foreach (['get', 'post', 'put', 'patch', 'delete', 'head', 'options'] as $method) {
            $route = $router->getNamedRoute($method);

            $this->assertInstanceOf('League\Route\Route', $route);

            $this->assertSame("/prefix/${method}/something", $route->getPath());
            $this->assertSame('handler', $route->getCallable());
            $this->assertSame('example.com', $route->getHost());
            $this->assertSame('http', $route->getScheme());
            $this->assertSame($group, $route->getParentGroup());
        }
    }

    /**
     * Asserts that an exception is thown when a named route cannot be found.
     */
    public function testExceptionIsThrownWhenNamedRouteIsNotFound()
    {
        $this->setExpectedException('InvalidArgumentException');

        (new RouteCollection)->getNamedRoute('something');
    }

    /**
     * Asserts that appropriately configured regex strings are added to patternMatchers.
     */
    public function testNewPatternMatchesCanBeAddedAtRuntime()
    {
        $router = new RouteCollection;

        $router->addPatternMatcher('mockMatcher', '[a-zA-Z]');

        $matchers = $this->getObjectAttribute($router, 'patternMatchers');

        $this->assertArrayHasKey('/{(.+?):mockMatcher}/', $matchers);
        $this->assertEquals('{$1:[a-zA-Z]}', $matchers['/{(.+?):mockMatcher}/']);
    }

    /**
     * Asserts that the collection will prep all routes and return a dispatcher.
     */
    public function testCollectionPrepsRoutesAndReturnsADispatcher()
    {
        $router = new RouteCollection;

        $uri = $this->getMock('Psr\Http\Message\UriInterface');
        $uri->expects($this->any())->method('getHost')->will($this->returnValue('example.com'));
        $uri->expects($this->any())->method('getScheme')->will($this->returnValue('http'));

        $request = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $request->expects($this->any())->method('getUri')->will($this->returnValue($uri));

        $router->get('get/something', 'handler')->setName('get')->setScheme('http')->setHost('example.com');
        $router->post('/post/something', 'handler')->setName('post')->setScheme('https');
        $router->put('put/something', 'handler')->setName('put')->setHost('sub.example.com');
        $router->patch('/patch/something', 'handler')->setName('patch');
        $router->delete('delete/something', 'handler')->setName('delete');
        $router->head('/head/something', 'handler')->setName('head');
        $router->options('options/something', 'handler')->setName('options');

        $dispatcher = $router->getDispatcher($request);

        $this->assertInstanceOf('League\Route\Dispatcher', $dispatcher);
        $this->assertCount(5, $router->getData()[0]);
    }

    /**
     * Asserts that registering a middleware proxies to the runner.
     */
    public function testMiddlewareMethodsProxyToRunner()
    {
        $callable = function () {};
        $runner   = $this->getMock('League\Route\Middleware\Runner');

        $runner->expects($this->once())->method('before')->with($this->equalTo($callable));
        $runner->expects($this->once())->method('after')->with($this->equalTo($callable));

        $router = new RouteCollection;

        $router->setMiddlewareRunner($runner);

        $router->before($callable);
        $router->after($callable);
    }
}
