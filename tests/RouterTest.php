<?php

declare(strict_types=1);

use League\Route\MatchStatus;
use League\Route\Router;
use League\Route\Strategy\ApplicationStrategy;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

test('router maps and returns route for each HTTP method', function () {
    $router   = new Router();
    $path     = '/something';
    $callable = function () {};

    foreach (['get', 'post', 'put', 'patch', 'delete', 'head', 'options'] as $method) {
        $route = $router->map($method, $path, $callable);
        expect($route->getMethod())->toBe($method);
        expect($route->getPath())->toBe($path);
        expect($route->getCallable())->toBe($callable);
    }
});

test('router maps and returns a route group with correct prefix', function () {
    $router   = new Router();
    $prefix   = '/something';
    $callable = static function () {};

    $group = $router->group($prefix, $callable);
    expect($group->getPrefix())->toBe($prefix);
});

test('router can set and retrieve a named route', function () {
    $router = new Router();
    $name   = 'route';

    $expected = $router
        ->map('get', '/something', function () {})
        ->setName($name)
    ;

    $actual = $router->getNamedRoute($name);
    expect($actual)->toBe($expected);
});

test('router throws an exception when retrieving a named route that does not exist', function () {
    expect(fn() => (new Router())->getNamedRoute('umm'))->toThrow(InvalidArgumentException::class);
});

test('new pattern matchers can be added at runtime', function () {
    $router = new class extends Router {
        public array $patternMatchers = [];
    };

    $router->addPatternMatcher('mockMatcher', '[a-zA-Z]');
    $matchers = $router->patternMatchers;

    expect(array_key_exists('/{(.+?):mockMatcher}/', $matchers))->toBeTrue();
    expect($matchers['/{(.+?):mockMatcher}/'])->toEqual('{$1:[a-zA-Z]}');
});

test('match returns found status for a registered route', function () {
    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/example/route');

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);

    $router = new Router();
    $router->map('GET', '/example/{something}', static function () {});

    $result = $router->match($request);

    expect($result->isFound())->toBeTrue();
    expect($result->getStatus())->toBe(MatchStatus::Found);
});

test('match returns not found status for an unregistered route', function () {
    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/does-not-exist');

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);

    $router = new Router();
    $router->map('GET', '/example/{something}', static function () {});

    $result = $router->match($request);

    expect($result->isFound())->toBeFalse();
    expect($result->getStatus())->toBe(MatchStatus::NotFound);
});

test('match returns method not allowed when the HTTP method does not match', function () {
    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/example/route');

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('POST');
    $request->allows('getUri')->andReturn($uri);

    $router = new Router();
    $router->map('GET', '/example/{something}', static function () {});

    $result = $router->match($request);

    expect($result->isFound())->toBeFalse();
    expect($result->isMethodNotAllowed())->toBeTrue();
    expect($result->getStatus())->toBe(MatchStatus::MethodNotAllowed);
    expect($result->getAllowedMethods())->toContain('GET');
});

test('getRoutes returns all registered routes', function () {
    $router = new Router();
    $router->get('/foo', static function () {});
    $router->post('/bar', static function () {});

    $routes = $router->getRoutes();
    expect($routes)->toHaveCount(2);
    expect($routes[0]->getPath())->toBe('/foo');
    expect($routes[1]->getPath())->toBe('/bar');
});

test('getRoutes includes named routes', function () {
    $router = new Router();
    $router->get('/foo', static function () {})->setName('foo.route');
    $router->get('/bar', static function () {});

    expect($router->getRoutes())->toHaveCount(2);
});

test('getRoutes includes routes registered inside a group', function () {
    $router = new Router();
    $router->get('/top', static function () {});
    $router->group('/api', function ($group) {
        $group->get('/users', static function () {});
    });

    $routes = $router->getRoutes();
    expect($routes)->toHaveCount(2);
    expect($routes[0]->getPath())->toBe('/top');
    expect($routes[1]->getPath())->toBe('/api/users');
});

test('getRoutes returns an empty array when no routes are registered', function () {
    expect((new Router())->getRoutes())->toBe([]);
});

test('getRoutes does not duplicate routes after multiple calls', function () {
    $router = new Router();
    $router->setStrategy(new ApplicationStrategy());
    $router->get('/foo', static function () {});
    $router->group('/api', function ($group) {
        $group->get('/bar', static function () {});
    });

    expect($router->getRoutes())->toHaveCount(2);
    expect($router->getRoutes())->toHaveCount(2);
});

test('setRoutesData injects cached state and allows matching', function () {
    $router = new Router();
    $router->map('GET', '/foo', static function () {});

    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/foo');

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);

    $router->prepareRoutes($request);
    $data = $router->getRoutesData();
    $map  = $router->getRouteMap();

    expect($data)->not->toBeEmpty();
    expect($map)->not->toBeEmpty();

    $newRouter = new Router();
    $newRouter->map('GET', '/foo', static function () {});
    $newRouter->setRoutesData($data, $map);

    expect($newRouter->match($request)->isFound())->toBeTrue();
});

test('match returns not found when the scheme condition does not match', function () {
    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/secure');
    $uri->allows('getScheme')->andReturn('http');

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);

    $router = new Router();
    $router->map('GET', '/secure', static function () {})->setScheme('https');

    expect($router->match($request)->isFound())->toBeFalse();
});

test('match returns not found when the host condition does not match', function () {
    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/api/users');
    $uri->allows('getHost')->andReturn('wrong.example.com');

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);

    $router = new Router();
    $router->map('GET', '/api/users', static function () {})->setHost('api.example.com');

    expect($router->match($request)->isFound())->toBeFalse();
});
