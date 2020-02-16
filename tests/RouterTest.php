<?php declare(strict_types=1);

namespace League\Route;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface, UriInterface};

class RouterTest extends TestCase
{
    /**
     * Asserts that the collection can map and return a route object.
     *
     * @return void
     */
    public function testCRouterMapsAndReturnsRoute(): void
    {
        $router   = new Router;
        $path     = '/something';
        $callable = function () {
        };

        foreach ([
            'get', 'post', 'put', 'patch', 'delete', 'head', 'options'
        ] as $method) {
            $route = $router->map($method, $path, $callable);

            $this->assertSame($method, $route->getMethod());
            $this->assertSame($path, $route->getPath());
            $this->assertSame($callable, $route->getCallable());
        }
    }

    /**
     * Asserts that the collection can map and return a route group object.
     *
     * @return void
     */
    public function testCollectionMapsAndReturnsGroup(): void
    {
        $router   = new Router;
        $prefix   = '/something';
        $callable = function () {
        };

        $group = $router->group($prefix, $callable);

        $this->assertSame($prefix, $group->getPrefix());
    }

    /**
     * Asserts that the collection can set a named route and retrieve it by name.
     *
     * @return void
     */
    public function testCollectionCanSetAndGetNamedRoute(): void
    {
        $router = new Router;
        $name   = 'route';

        $expected = $router
            ->map('get', '/something', function () {
            })
            ->setName($name)
        ;

        $actual = $router->getNamedRoute($name);

        $this->assertSame($expected, $actual);
    }

    /**
     * Asserts that an exception is thrown when trying to get a named route that does not exist.
     *
     * @return void
     */
    public function testCollectionThrowsExceptionWhenAttemptingToGetNamedRouteThatDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Router)->getNamedRoute('umm');
    }

    /**
     * Asserts that appropriately configured regex strings are added to patternMatchers.
     *
     * @return void
     */
    public function testNewPatternMatchesCanBeAddedAtRuntime(): void
    {
        $router = new Router;
        $router->addPatternMatcher('mockMatcher', '[a-zA-Z]');
        $matchers = $this->getObjectAttribute($router, 'patternMatchers');

        $this->assertArrayHasKey('/{(.+?):mockMatcher}/', $matchers);
        $this->assertEquals('{$1:[a-zA-Z]}', $matchers['/{(.+?):mockMatcher}/']);
    }



    /**
     * Asserts that appropriately configured regex strings are added to patternMatchers.
     *
     * @return void
     */
    public function testRemoveRoute(): void
    {
        $router = new Router;
        $router->map('GET', '/route-we-dont-want-or-need', 'someController::someAction');
        $routes = $router->getRoutes();
        $route = $routes[0];
        $router->removeRoute($route);
        $routes = $router->getRoutes();
        $this->assertEmpty($routes);
    }
}
