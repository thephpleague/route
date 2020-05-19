<?php

declare(strict_types=1);

namespace League\Route;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function testCRouterMapsAndReturnsRoute(): void
    {
        $router   = new Router();
        $path     = '/something';
        $callable = function () {
        };

        foreach (
            ['get', 'post', 'put', 'patch', 'delete', 'head', 'options'] as $method
        ) {
            $route = $router->map($method, $path, $callable);
            $this->assertSame($method, $route->getMethod());
            $this->assertSame($path, $route->getPath());
            $this->assertSame($callable, $route->getCallable());
        }
    }

    public function testCollectionMapsAndReturnsGroup(): void
    {
        $router   = new Router();
        $prefix   = '/something';
        $callable = static function () {
        };

        $group = $router->group($prefix, $callable);
        $this->assertSame($prefix, $group->getPrefix());
    }

    public function testCollectionCanSetAndGetNamedRoute(): void
    {
        $router = new Router();
        $name   = 'route';

        $expected = $router
            ->map('get', '/something', function () {
            })
            ->setName($name)
        ;

        $actual = $router->getNamedRoute($name);
        $this->assertSame($expected, $actual);
    }

    public function testCollectionThrowsExceptionWhenAttemptingToGetNamedRouteThatDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Router())->getNamedRoute('umm');
    }

    /**
     * Asserts that appropriately configured regex strings are added to patternMatchers.
     *
     * @return void
     */
    public function testNewPatternMatchesCanBeAddedAtRuntime(): void
    {
        $router = new class () extends Router
        {
            public $patternMatchers = [];
        };

        $router->addPatternMatcher('mockMatcher', '[a-zA-Z]');
        $matchers = $router->patternMatchers;
        $this->assertArrayHasKey('/{(.+?):mockMatcher}/', $matchers);
        $this->assertEquals('{$1:[a-zA-Z]}', $matchers['/{(.+?):mockMatcher}/']);
    }
}
