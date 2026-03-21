<?php

declare(strict_types=1);

use League\Route\Route;
use League\Route\RouteCollectionInterface;
use League\Route\RouteGroup;
use League\Route\Router;
use League\Route\Strategy\JsonStrategy;

test('route group is invoked and propagates host, scheme, and port to all registered routes', function () {
    $callback = static function () {};

    $route = Mockery::mock(Route::class);
    $route->allows('setParentGroup')->andReturnSelf();
    $route->allows('getStrategy')->andReturnNull();
    $route->shouldReceive('setHost')->times(8)->with('example.com')->andReturnSelf();
    $route->shouldReceive('setScheme')->times(8)->with('https')->andReturnSelf();
    $route->shouldReceive('setPort')->times(8)->with(8080)->andReturnSelf();

    $router = Mockery::mock(Router::class);
    $router
        ->shouldReceive('map')
        ->times(7)
        ->with(
            Mockery::pattern('/^(GET|POST|PUT|PATCH|DELETE|OPTIONS|HEAD)$/'),
            '/acme/route',
            $callback,
        )
        ->andReturn($route)
    ;

    $group = new RouteGroup('/acme', function ($route) use ($callback) {
        $route->get('/route', $callback)->setHost('example.com')->setPort(8080)->setScheme('https');
        $route->post('/route', $callback);
        $route->put('/route', $callback);
        $route->patch('/route', $callback);
        $route->delete('/route', $callback);
        $route->options('/route', $callback);
        $route->head('/route', $callback);
    }, $router);

    $group->setHost('example.com')->setScheme('https')->setPort(8080);
    $group();

    expect($group)->toBeInstanceOf(RouteGroup::class);
});

test('route group sets its strategy on each registered route', function () {
    $callback = static function () {};

    $router = Mockery::mock(RouteCollectionInterface::class);

    $strategy = Mockery::mock(JsonStrategy::class);

    $route = Mockery::mock(Route::class);

    $router
        ->shouldReceive('map')
        ->once()
        ->with('GET', '/acme/route', $callback)
        ->andReturn($route)
    ;

    $route->allows('setParentGroup')->andReturnSelf();
    $route->allows('getStrategy')->andReturnNull();
    $route->shouldReceive('setStrategy')->once()->with($strategy)->andReturnSelf();

    $group = new RouteGroup('/acme', function ($route) use ($callback) {
        $route->get('/route', $callback);
    }, $router);

    $group->setStrategy($strategy);
    $group();

    expect($group)->toBeInstanceOf(RouteGroup::class);
});

test('named routes registered inside a group are retrievable from the router', function () {
    $router   = new Router();
    $name     = 'route';
    $expected = null;

    $router->group('/acme', function (RouteGroup $group) use ($name, &$expected) {
        $expected = $group->get('/route', function () {})->setName($name);
    });

    $actual = $router->getNamedRoute($name);

    expect($actual)->not->toBeNull();
    expect($actual)->toBe($expected);
});
