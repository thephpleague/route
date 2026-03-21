<?php

declare(strict_types=1);

use League\Route\MatchStatus;
use League\Route\Router;
use League\Route\Strategy\ApplicationStrategy;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

test('router maps and returns route for each HTTP method', function () {
    $router = new Router();
    $path = '/something';
    $callable = function () {};

    foreach (['get', 'post', 'put', 'patch', 'delete', 'head', 'options'] as $method) {
        $route = $router->map($method, $path, $callable);
        expect($route->getMethod())->toBe($method);
        expect($route->getPath())->toBe($path);
        expect($route->getCallable())->toBe($callable);
    }
});

test('router maps and returns a route group with correct prefix', function () {
    $router = new Router();
    $prefix = '/something';
    $callable = static function () {};

    $group = $router->group($prefix, $callable);
    expect($group->getPrefix())->toBe($prefix);
});

test('router can set and retrieve a named route', function () {
    $router = new Router();
    $name = 'route';

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
    $map = $router->getRouteMap();

    expect($data)->not->toBeEmpty();
    expect($map)->not->toBeEmpty();

    $newRouter = new Router();
    $newRouter->map('GET', '/foo', static function () {});
    $newRouter->setRoutesData($data, $map);

    expect($newRouter->match($request)->isFound())->toBeTrue();
});

test('match returns condition not met when the scheme condition does not match', function () {
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

    $result = $router->match($request);

    expect($result->isFound())->toBeFalse();
    expect($result->isConditionNotMet())->toBeTrue();
    expect($result->getStatus())->toBe(MatchStatus::ConditionNotMet);
});

test('match returns condition not met when the host condition does not match', function () {
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

    $result = $router->match($request);

    expect($result->isFound())->toBeFalse();
    expect($result->isConditionNotMet())->toBeTrue();
    expect($result->getStatus())->toBe(MatchStatus::ConditionNotMet);
});

test('defineMiddlewareGroup registers a named middleware group', function () {
    $router = new Router();

    $router->defineMiddlewareGroup('auth', ['App\Middleware\AuthMiddleware']);

    expect($router->getMiddlewareGroup('auth'))->toBe(['App\Middleware\AuthMiddleware']);
});

test('middlewareGroup applies named group middleware to routes via a group', function () {
    $router = new Router();
    $router->defineMiddlewareGroup('auth', ['App\Middleware\AuthMiddleware', 'App\Middleware\VerifyToken']);

    $group = $router->group('/api', function ($group) {
        $group->get('/users', static function () {});
    });
    $group->middlewareGroup('auth');

    $routes = $router->getRoutes();
    $route = $routes[0];

    $parentGroup = $route->getParentGroup();
    expect($parentGroup)->not->toBeNull();

    $stack = iterator_to_array($parentGroup->getMiddlewareStack());
    expect($stack)->toContain('App\Middleware\AuthMiddleware');
    expect($stack)->toContain('App\Middleware\VerifyToken');
});

test('middlewareGroup on router applies named group middleware globally', function () {
    $router = new Router();
    $router->defineMiddlewareGroup('logging', ['App\Middleware\LogMiddleware']);
    $router->middlewareGroup('logging');

    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/ping');

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);

    $router->map('GET', '/ping', static function () {});
    $router->prepareRoutes($request);

    $stack = iterator_to_array($router->getMiddlewareStack());
    expect($stack)->toContain('App\Middleware\LogMiddleware');
});

test('middlewareGroup throws InvalidArgumentException for an undefined group name', function () {
    $router = new Router();

    expect(fn() => $router->getMiddlewareGroup('nonexistent'))->toThrow(InvalidArgumentException::class);
});

test('multiple middleware groups can be applied to the same route group', function () {
    $router = new Router();
    $router->defineMiddlewareGroup('auth', ['App\Middleware\AuthMiddleware']);
    $router->defineMiddlewareGroup('throttle', ['App\Middleware\ThrottleMiddleware']);

    $group = $router->group('/api', function ($group) {
        $group->get('/orders', static function () {});
    });
    $group->middlewareGroup('auth');
    $group->middlewareGroup('throttle');

    $routes = $router->getRoutes();
    $parentGroup = $routes[0]->getParentGroup();
    expect($parentGroup)->not->toBeNull();

    $stack = iterator_to_array($parentGroup->getMiddlewareStack());
    expect($stack)->toContain('App\Middleware\AuthMiddleware');
    expect($stack)->toContain('App\Middleware\ThrottleMiddleware');
});
