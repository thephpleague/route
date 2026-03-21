<?php

declare(strict_types=1);

use League\Route\Cache\FileCache;
use League\Route\Cache\Router;
use League\Route\Router as MainRouter;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\SimpleCache\CacheInterface;

test('dispatches a found route and then serves the same route from cache', function () {
    $cacheFile = __DIR__ . '/routeCache.cache';

    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/example/route');

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);
    $request->allows('withAttribute')->andReturn($request);

    $cacheStore = new FileCache($cacheFile, 86400);

    $router = new Router(function (MainRouter $router): MainRouter {
        $router->map('GET', '/example/{something}', function (
            ServerRequestInterface $request,
            array $args,
        ): ResponseInterface {
            expect($args)->toBe(['something' => 'route']);
            return Mockery::mock(ResponseInterface::class);
        });

        return $router;
    }, $cacheStore);

    $firstResponse = $router->dispatch($request);
    expect($firstResponse)->toBeInstanceOf(ResponseInterface::class);
    expect($cacheFile)->toBeFile();

    $secondResponse = $router->dispatch($request);
    expect($secondResponse)->toBeInstanceOf(ResponseInterface::class);

    unlink($cacheFile);
});

test('dispatches without touching cache when caching is disabled', function () {
    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/example/route');

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);
    $request->allows('withAttribute')->andReturn($request);

    /** @var CacheInterface&MockInterface $cache */
    $cache = Mockery::mock(CacheInterface::class);
    $cache->shouldNotReceive('get');
    $cache->shouldNotReceive('set');

    $router = new Router(function (MainRouter $router): MainRouter {
        $router->map('GET', '/example/{something}', function (
            ServerRequestInterface $request,
            array $args,
        ): ResponseInterface {
            return Mockery::mock(ResponseInterface::class);
        });

        return $router;
    }, $cache, false);

    $response = $router->dispatch($request);
    expect($response)->toBeInstanceOf(ResponseInterface::class);
});

test('rebuilds router and overwrites cache when route signature changes', function () {
    $cacheFile = __DIR__ . '/routeCacheSignature.cache';

    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/example/route');

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);
    $request->allows('withAttribute')->andReturn($request);

    $cacheStore = new FileCache($cacheFile, 86400);

    $firstRouter = new Router(function (MainRouter $router): MainRouter {
        $router->map('GET', '/example/{something}', function (
            ServerRequestInterface $request,
            array $args,
        ): ResponseInterface {
            return Mockery::mock(ResponseInterface::class);
        });

        return $router;
    }, $cacheStore);

    $firstRouter->dispatch($request);
    expect($cacheFile)->toBeFile();

    /** @var UriInterface&MockInterface $uri2 */
    $uri2 = Mockery::mock(UriInterface::class);
    $uri2->allows('getPath')->andReturn('/example/route');

    /** @var ServerRequestInterface&MockInterface $request2 */
    $request2 = Mockery::mock(ServerRequestInterface::class);
    $request2->allows('getMethod')->andReturn('GET');
    $request2->allows('getUri')->andReturn($uri2);
    $request2->allows('withAttribute')->andReturn($request2);

    $secondRouter = new Router(function (MainRouter $router): MainRouter {
        $router->map('GET', '/example/{something}', function (
            ServerRequestInterface $request,
            array $args,
        ): ResponseInterface {
            return Mockery::mock(ResponseInterface::class);
        });
        $router->map('POST', '/other', function (
            ServerRequestInterface $request,
            array $args,
        ): ResponseInterface {
            return Mockery::mock(ResponseInterface::class);
        });

        return $router;
    }, $cacheStore);

    $response = $secondRouter->dispatch($request2);
    expect($response)->toBeInstanceOf(ResponseInterface::class);

    unlink($cacheFile);
});

test('match delegates to the inner router and returns a found result', function () {
    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/example/route');

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);

    /** @var CacheInterface&MockInterface $cache */
    $cache = Mockery::mock(CacheInterface::class);
    $cache->allows('get')->andReturn(null);
    $cache->allows('set')->andReturn(true);

    $router = new Router(function (MainRouter $router): MainRouter {
        $router->map('GET', '/example/{something}', static function () {});
        return $router;
    }, $cache);

    $result = $router->match($request);
    expect($result->isFound())->toBeTrue();
});

test('handle delegates to dispatch and returns a response', function () {
    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/example/route');

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);
    $request->allows('withAttribute')->andReturn($request);

    /** @var CacheInterface&MockInterface $cache */
    $cache = Mockery::mock(CacheInterface::class);
    $cache->allows('get')->andReturn(null);
    $cache->allows('set')->andReturn(true);

    $router = new Router(function (MainRouter $router): MainRouter {
        $router->map('GET', '/example/{something}', function (
            ServerRequestInterface $request,
            array $args,
        ): ResponseInterface {
            return Mockery::mock(ResponseInterface::class);
        });
        return $router;
    }, $cache);

    $response = $router->handle($request);
    expect($response)->toBeInstanceOf(ResponseInterface::class);
});

test('recovers from a corrupt cache file and dispatches successfully', function () {
    $cacheFile = sys_get_temp_dir() . '/league_route_corrupt_' . uniqid() . '.cache';
    file_put_contents($cacheFile, 'this is not valid serialized data');

    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/example/route');

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);
    $request->allows('withAttribute')->andReturn($request);

    $cacheStore = new FileCache($cacheFile, 86400);

    $router = new Router(function (MainRouter $router): MainRouter {
        $router->map('GET', '/example/{something}', function (
            ServerRequestInterface $request,
            array $args,
        ): ResponseInterface {
            return Mockery::mock(ResponseInterface::class);
        });
        return $router;
    }, $cacheStore);

    $response = $router->dispatch($request);
    expect($response)->toBeInstanceOf(ResponseInterface::class);

    @unlink($cacheFile);
});

test('builder that returns void still registers routes correctly', function () {
    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/example/route');

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);
    $request->allows('withAttribute')->andReturn($request);

    /** @var CacheInterface&MockInterface $cache */
    $cache = Mockery::mock(CacheInterface::class);
    $cache->allows('get')->andReturn(null);
    $cache->allows('set')->andReturn(true);

    $router = new Router(function (MainRouter $router): void {
        $router->map('GET', '/example/{something}', function (
            ServerRequestInterface $request,
            array $args,
        ): ResponseInterface {
            return Mockery::mock(ResponseInterface::class);
        });
    }, $cache);

    $response = $router->dispatch($request);
    expect($response)->toBeInstanceOf(ResponseInterface::class);
});
