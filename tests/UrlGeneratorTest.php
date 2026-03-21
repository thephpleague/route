<?php

declare(strict_types=1);

use League\Route\Cache\Router as CacheRouter;
use League\Route\Router;
use Mockery\MockInterface;
use Psr\SimpleCache\CacheInterface;

test('generates a URL by substituting a single path parameter', function () {
    $router = new Router();
    $router->get('/users/{id}', static function () {})->setName('users.show');

    expect($router->generateUrl('users.show', ['id' => '42']))->toBe('/users/42');
});

test('throws InvalidArgumentException when a required path parameter is missing', function () {
    $router = new Router();
    $router->get('/users/{id}', static function () {})->setName('users.show');

    expect(fn() => $router->generateUrl('users.show'))->toThrow(InvalidArgumentException::class);
});

test('appends extra substitutions as a query string', function () {
    $router = new Router();
    $router->get('/users/{id}', static function () {})->setName('users.show');

    expect($router->generateUrl('users.show', ['id' => '42', 'page' => '2']))->toBe('/users/42?page=2');
});

test('generates a URL for a route with a named pattern matcher shortcut', function () {
    $router = new Router();
    $router->get('/users/{id:number}', static function () {})->setName('users.show');

    expect($router->generateUrl('users.show', ['id' => '42']))->toBe('/users/42');
});

test('generates a URL with multiple path parameters', function () {
    $router = new Router();
    $router->get('/users/{userId}/posts/{postId}', static function () {})->setName('user.post');

    expect($router->generateUrl('user.post', ['userId' => '5', 'postId' => '99']))->toBe('/users/5/posts/99');
});

test('throws InvalidArgumentException when the named route does not exist', function () {
    expect(fn() => (new Router())->generateUrl('nonexistent'))->toThrow(InvalidArgumentException::class);
});

test('generates a URL with no parameters for a static route', function () {
    $router = new Router();
    $router->get('/about', static function () {})->setName('about');

    expect($router->generateUrl('about'))->toBe('/about');
});

test('appends multiple extra parameters as a query string', function () {
    $router = new Router();
    $router->get('/search', static function () {})->setName('search');

    $url = $router->generateUrl('search', ['q' => 'hello', 'page' => '1', 'sort' => 'asc']);

    expect($url)->toBe('/search?q=hello&page=1&sort=asc');
});

test('throws InvalidArgumentException listing the missing parameter', function () {
    $router = new Router();
    $router->get('/users/{id}/posts/{postId}', static function () {})->setName('user.post');

    expect(fn() => $router->generateUrl('user.post', ['id' => '5']))
        ->toThrow(InvalidArgumentException::class);
});

test('cache router delegates URL generation to the inner router', function () {
    /** @var CacheInterface&MockInterface $cache */
    $cache = Mockery::mock(CacheInterface::class);

    $router = new CacheRouter(function (Router $r): Router {
        $r->get('/users/{id}', static function () {})->setName('users.show');
        return $r;
    }, $cache);

    expect($router->generateUrl('users.show', ['id' => '7']))->toBe('/users/7');
});

test('cache router throws InvalidArgumentException for a missing named route', function () {
    /** @var CacheInterface&MockInterface $cache */
    $cache = Mockery::mock(CacheInterface::class);

    $router = new CacheRouter(function (Router $r): Router {
        return $r;
    }, $cache);

    expect(fn() => $router->generateUrl('nonexistent'))->toThrow(InvalidArgumentException::class);
});

test('generates a URL with an optional segment when the parameter is provided', function () {
    $router = new Router();
    $router->get('/blog[/{page:number}]', static function () {})->setName('blog.list');

    expect($router->generateUrl('blog.list', ['page' => '2']))->toBe('/blog/2');
});

test('generates a URL omitting the optional segment when the parameter is not provided', function () {
    $router = new Router();
    $router->get('/blog[/{page:number}]', static function () {})->setName('blog.list');

    expect($router->generateUrl('blog.list'))->toBe('/blog');
});

test('generates a URL using default vars for an optional segment', function () {
    $router = new Router();
    $router->get('/blog[/{page:number}]', static function () {})->setName('blog.list')->setVars(['page' => '1']);

    expect($router->generateUrl('blog.list'))->toBe('/blog/1');
});

test('substitution overrides default var for an optional segment', function () {
    $router = new Router();
    $router->get('/blog[/{page:number}]', static function () {})->setName('blog.list')->setVars(['page' => '1']);

    expect($router->generateUrl('blog.list', ['page' => '3']))->toBe('/blog/3');
});

test('still throws for missing required parameters when optional segments are present', function () {
    $router = new Router();
    $router->get('/users/{id}[/{action}]', static function () {})->setName('users.action');

    expect(fn() => $router->generateUrl('users.action'))->toThrow(InvalidArgumentException::class);
});

test('generates a URL with nested optional segments when only the outer parameter is provided', function () {
    $router = new Router();
    $router->get('/archive[/{year}[/{month}]]', static function () {})->setName('archive');

    expect($router->generateUrl('archive', ['year' => '2024']))->toBe('/archive/2024');
});

test('generates a URL with nested optional segments when both parameters are provided', function () {
    $router = new Router();
    $router->get('/archive[/{year}[/{month}]]', static function () {})->setName('archive');

    expect($router->generateUrl('archive', ['year' => '2024', 'month' => '03']))->toBe('/archive/2024/03');
});

test('generates a URL with nested optional segments when no parameters are provided', function () {
    $router = new Router();
    $router->get('/archive[/{year}[/{month}]]', static function () {})->setName('archive');

    expect($router->generateUrl('archive'))->toBe('/archive');
});
