<?php

declare(strict_types=1);

namespace League\Route;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ServerRequestInterface, UriInterface};

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
            public array $patternMatchers = [];
        };

        $router->addPatternMatcher('mockMatcher', '[a-zA-Z]');
        $matchers = $router->patternMatchers;
        $this->assertArrayHasKey('/{(.+?):mockMatcher}/', $matchers);
        $this->assertEquals('{$1:[a-zA-Z]}', $matchers['/{(.+?):mockMatcher}/']);
    }

    public function testMatchReturnsFoundForRegisteredRoute(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);

        $uri->method('getPath')->willReturn('/example/route');
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);

        $router = new Router();
        $router->map('GET', '/example/{something}', static function () {});

        $result = $router->match($request);

        $this->assertTrue($result->isFound());
        $this->assertSame(MatchStatus::Found, $result->getStatus());
    }

    public function testMatchReturnsNotFoundForUnregisteredRoute(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);

        $uri->method('getPath')->willReturn('/does-not-exist');
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);

        $router = new Router();
        $router->map('GET', '/example/{something}', static function () {});

        $result = $router->match($request);

        $this->assertFalse($result->isFound());
        $this->assertSame(MatchStatus::NotFound, $result->getStatus());
    }

    public function testMatchReturnsMethodNotAllowedWhenMethodDoesNotMatch(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);

        $uri->method('getPath')->willReturn('/example/route');
        $request->method('getMethod')->willReturn('POST');
        $request->method('getUri')->willReturn($uri);

        $router = new Router();
        $router->map('GET', '/example/{something}', static function () {});

        $result = $router->match($request);

        $this->assertFalse($result->isFound());
        $this->assertTrue($result->isMethodNotAllowed());
        $this->assertSame(MatchStatus::MethodNotAllowed, $result->getStatus());
        $this->assertContains('GET', $result->getAllowedMethods());
    }

    public function testGetRoutesReturnsRegisteredRoutes(): void
    {
        $router = new Router();
        $router->get('/foo', static function () {});
        $router->post('/bar', static function () {});

        $routes = $router->getRoutes();
        $this->assertCount(2, $routes);
        $this->assertSame('/foo', $routes[0]->getPath());
        $this->assertSame('/bar', $routes[1]->getPath());
    }

    public function testGetRoutesIncludesNamedRoutes(): void
    {
        $router = new Router();
        $router->get('/foo', static function () {})->setName('foo.route');
        $router->get('/bar', static function () {});

        $routes = $router->getRoutes();
        $this->assertCount(2, $routes);
    }

    public function testGetRoutesIncludesGroupRoutes(): void
    {
        $router = new Router();
        $router->get('/top', static function () {});
        $router->group('/api', function ($group) {
            $group->get('/users', static function () {});
        });

        $routes = $router->getRoutes();
        $this->assertCount(2, $routes);
        $this->assertSame('/top', $routes[0]->getPath());
        $this->assertSame('/api/users', $routes[1]->getPath());
    }

    public function testGetRoutesReturnsEmptyArrayForEmptyRouter(): void
    {
        $router = new Router();
        $this->assertSame([], $router->getRoutes());
    }

    public function testGetRoutesDoesNotDuplicateAfterDispatch(): void
    {
        $router = new Router();
        $router->setStrategy(new \League\Route\Strategy\ApplicationStrategy());
        $router->get('/foo', static function () {});
        $router->group('/api', function ($group) {
            $group->get('/bar', static function () {});
        });

        $routesBefore = $router->getRoutes();
        $this->assertCount(2, $routesBefore);

        $routesAgain = $router->getRoutes();
        $this->assertCount(2, $routesAgain);
    }

    public function testSetRoutesDataInjectsCachedState(): void
    {
        $router = new Router();
        $router->map('GET', '/foo', static function () {});

        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/foo');
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);

        $router->prepareRoutes($request);
        $data = $router->getRoutesData();
        $map = $router->getRouteMap();

        $this->assertNotEmpty($data);
        $this->assertNotEmpty($map);

        $newRouter = new Router();
        $newRouter->map('GET', '/foo', static function () {});
        $newRouter->setRoutesData($data, $map);

        $result = $newRouter->match($request);
        $this->assertTrue($result->isFound());
    }

    public function testMatchWithSchemeConditionReturnsNotFound(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);

        $uri->method('getPath')->willReturn('/secure');
        $uri->method('getScheme')->willReturn('http');
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);

        $router = new Router();
        $router->map('GET', '/secure', static function () {})->setScheme('https');

        $result = $router->match($request);
        $this->assertFalse($result->isFound());
    }

    public function testMatchWithHostConditionReturnsNotFound(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);

        $uri->method('getPath')->willReturn('/api/users');
        $uri->method('getHost')->willReturn('wrong.example.com');
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);

        $router = new Router();
        $router->map('GET', '/api/users', static function () {})->setHost('api.example.com');

        $result = $router->match($request);
        $this->assertFalse($result->isFound());
    }
}
