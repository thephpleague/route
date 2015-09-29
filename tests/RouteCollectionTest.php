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

        $group = $router->group('/prefix', function ($collection) {
            $this->assertInstanceOf('League\Route\RouteGroup', $collection);

            $collection->get('get/something', 'handler')->setName('get');
            $collection->post('/post/something', 'handler')->setName('post');
            $collection->put('put/something', 'handler')->setName('put');
            $collection->patch('/patch/something', 'handler')->setName('patch');
            $collection->delete('delete/something', 'handler')->setName('delete');
            $collection->head('/head/something', 'handler')->setName('head');
            $collection->options('options/something', 'handler')->setName('options');
        })->setHost('example.com')->setScheme('http');

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
}
