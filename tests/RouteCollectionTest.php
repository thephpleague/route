<?php

namespace League\Route\Test;

use League\Route\RouteCollection;
use League\Route\Strategy\RestfulStrategy;
use League\Route\Strategy\RequestResponseStrategy;
use League\Route\Strategy\UriStrategy;

class RouteCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that routes are set via convenience methods
     *
     * @return void
     */
    public function testSetsRoutesViaConvenienceMethods()
    {
        $router = new RouteCollection;

        $restfulStrategy = new RestfulStrategy;
        $reqResStrategy = new RequestResponseStrategy;
        $uriStrategy = new uriStrategy;

        $router->get('/route/{wildcard}', 'handler_get', $restfulStrategy);
        $router->post('/route/{wildcard}', 'handler_post', $uriStrategy);
        $router->put('/route/{wildcard}', 'handler_put', $reqResStrategy);
        $router->patch('/route/{wildcard}', 'handler_patch');
        $router->delete('/route/{wildcard}', 'handler_delete');
        $router->head('/route/{wildcard}', 'handler_head');
        $router->options('/route/{wildcard}', 'handler_options');

        $routes = (new \ReflectionClass($router))->getProperty('routes');
        $routes->setAccessible(true);
        $routes = $routes->getValue($router);

        $this->assertCount(7, $routes);
        $this->assertSame($routes['handler_get'], ['strategy' => $restfulStrategy]);
        $this->assertSame($routes['handler_post'], ['strategy' => $uriStrategy]);
        $this->assertSame($routes['handler_put'], ['strategy' => $reqResStrategy]);
        $this->assertEquals($routes['handler_patch'], ['strategy' => $reqResStrategy]);
        $this->assertEquals($routes['handler_delete'], ['strategy' => $reqResStrategy]);
        $this->assertEquals($routes['handler_head'], ['strategy' => $reqResStrategy]);
        $this->assertEquals($routes['handler_options'], ['strategy' => $reqResStrategy]);
    }

    /**
     * Asserts that routes are set via convenience methods with Closures
     *
     * @return void
     */
    public function testSetsRoutesViaConvenienceMethodsWithClosures()
    {
        $router = new RouteCollection;

        $router->get('/route/{wildcard}', function () {
            return 'get';
        });
        $router->post('/route/{wildcard}', function () {
            return 'post';
        });
        $router->put('/route/{wildcard}', function () {
            return 'put';
        });
        $router->patch('/route/{wildcard}', function () {
            return 'patch';
        });
        $router->delete('/route/{wildcard}', function () {
            return 'delete';
        });
        $router->head('/route/{wildcard}', function () {
            return 'head';
        });
        $router->options('/route/{wildcard}', function () {
            return 'options';
        });

        $routes = (new \ReflectionClass($router))->getProperty('routes');
        $routes->setAccessible(true);
        $routes = $routes->getValue($router);

        $this->assertCount(7, $routes);

        foreach ($routes as $route) {
            $this->assertArrayHasKey('callback', $route);
            $this->assertArrayHasKey('strategy', $route);
        }
    }

    /**
     * Asserts that global strategy is used when set
     *
     * @return void
     */
    public function testGlobalStrategyIsUsedWhenSet()
    {
        $router = new RouteCollection;

        $restfulStrategy = new RestfulStrategy;
        $reqResStrategy = new RequestResponseStrategy;
        $uriStrategy = new uriStrategy;

        $router->setStrategy($uriStrategy);

        $router->get('/route/{wildcard}', 'handler_get', $restfulStrategy);
        $router->post('/route/{wildcard}', 'handler_post', $uriStrategy);
        $router->put('/route/{wildcard}', 'handler_put', $reqResStrategy);
        $router->patch('/route/{wildcard}', 'handler_patch');
        $router->delete('/route/{wildcard}', 'handler_delete');
        $router->head('/route/{wildcard}', 'handler_head');
        $router->options('/route/{wildcard}', 'handler_options');

        $routes = (new \ReflectionClass($router))->getProperty('routes');
        $routes->setAccessible(true);
        $routes = $routes->getValue($router);

        $this->assertCount(7, $routes);
        $this->assertSame($routes['handler_get'], ['strategy' => $uriStrategy]);
        $this->assertSame($routes['handler_post'], ['strategy' => $uriStrategy]);
        $this->assertSame($routes['handler_put'], ['strategy' => $uriStrategy]);
        $this->assertSame($routes['handler_patch'], ['strategy' => $uriStrategy]);
        $this->assertSame($routes['handler_delete'], ['strategy' => $uriStrategy]);
        $this->assertSame($routes['handler_head'], ['strategy' => $uriStrategy]);
        $this->assertSame($routes['handler_options'], ['strategy' => $uriStrategy]);
    }

    /**
     * Asserts that `getDispatcher` method returns correct instance
     *
     * @return void
     */
    public function testCollectionReturnsDispatcher()
    {
        $router = new RouteCollection;

        $this->assertInstanceOf('League\Route\Dispatcher', $router->getDispatcher());
        $this->assertInstanceOf('FastRoute\Dispatcher\GroupCountBased', $router->getDispatcher());
    }

    /**
     * Asserts that `getDispatcher` method returns correct instance with global strategy
     *
     * @return void
     */
    public function testCollectionReturnsDispatcherWithGlobalStrategy()
    {
        $router = new RouteCollection;

        $router->setStrategy(new RequestResponseStrategy);

        $this->assertInstanceOf('League\Route\Dispatcher', $router->getDispatcher());
        $this->assertInstanceOf('FastRoute\Dispatcher\GroupCountBased', $router->getDispatcher());
    }

    /**
     * Asserts that appropriately configured regex strings are added to patternMatchers.
     *
     * @return void
     */
    public function testNewPatternMatchesCanBeAddedAtRuntime()
    {
        $router = new RouteCollection;

        $router->addPatternMatcher('mockMatcher', '[a-zA-Z]');

        $matchers = $this->getObjectAttribute($router, "patternMatchers");

        $this->assertArrayHasKey('/{(.+?):mockMatcher}/', $matchers);
        $this->assertEquals('{$1:[a-zA-Z]}', $matchers['/{(.+?):mockMatcher}/']);
    }
}
