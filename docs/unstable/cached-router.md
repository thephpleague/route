---
layout: post
title: Cached Router
sections:
    Introduction: introduction
    How It Works: how-it-works
    Usage: usage
    Cache Stores: cache-stores
    Cache Invalidation: cache-invalidation
    Using RouterInterface: using-routerinterface
---
## Introduction

Route provides a cached router implementation that significantly improves performance on larger applications by caching compiled FastRoute data. Unlike earlier versions, the cached router is now production-ready and no longer in BETA.

The `League\Route\Cache\Router` class works by caching the expensive FastRoute route compilation step, whilst still running your builder function on every request to ensure routes and handlers remain fresh. This means you get near-zero startup overhead on subsequent requests without sacrificing dynamic route registration or live handler resolution.

## How It Works

The cached router uses a builder pattern where you provide a callable that configures your router:

1. The builder function registers all your routes by calling `$router->map()`, `$router->group()`, etc.
2. Routes are compiled using FastRoute, which is expensive and CPU-intensive.
3. The compiled route data is hashed and cached in the storage backend.
4. On subsequent requests, the compiled data is retrieved from cache, skipping the expensive compilation step.
5. The builder runs again to ensure Route objects are fresh and your handlers are resolved at request-time.
6. If the signature hash changes (routes were modified), the cache is automatically invalidated and rebuilt.
7. If a cache file becomes corrupt, the router detects this and rebuilds the cache automatically.

This design ensures:

- Fast startup times after the first request
- Reliable cache invalidation when routes change
- No serialisation of your entire application or controller instances
- Support for closures and container-resolved handlers
- Automatic recovery from corruption

## Usage

Using the cached router is very similar to the standard router, but you pass a builder callable instead of configuring it directly:

~~~php
<?php declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$cacheStore = new League\Route\Cache\FileCache('/path/to/cache/file.cache', $ttl = 86400);

$cachedRouter = new League\Route\Cache\Router(
    function (League\Route\Router $router): League\Route\Router {
        $router->map('GET', '/', function (ServerRequestInterface $request): ResponseInterface {
            $response = new Laminas\Diactoros\Response;
            $response->getBody()->write('<h1>Hello, World!</h1>');
            return $response;
        });

        $router->map('GET', '/users/{id}', 'UserController::show');

        return $router;
    },
    $cacheStore
);

$request = Laminas\Diactoros\ServerRequestFactory::fromGlobals(
    $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES
);

$response = $cachedRouter->dispatch($request);

(new Laminas\HttpHandlerRunner\Emitter\SapiEmitter)->emit($response);
~~~

On the first request, the builder will be invoked, routes compiled, and the result cached. On subsequent requests, the cached compiled data is used, and your builder is still called to instantiate fresh Route objects.

You can also pass optional parameters to customise caching behaviour:

~~~php
<?php declare(strict_types=1);

$cachedRouter = new League\Route\Cache\Router(
    $builder,
    $cacheStore,
    cacheEnabled: true,
    cacheKey: 'my-custom-key'
);
~~~

- `cacheEnabled: bool` (default: true) - Set to false to disable caching temporarily
- `cacheKey: string` (default: 'route') - Custom cache key for multiple router instances

## Cache Stores

The cached router can use any [PSR-16](https://www.php-fig.org/psr/psr-16/) simple cache implementation. Route provides a file-based PSR-16 implementation, but you can use any PSR-16 compatible store.

### FileCache

Route includes a `League\Route\Cache\FileCache` implementation that stores the compiled route data in a file:

~~~php
<?php declare(strict_types=1);

$cache = new League\Route\Cache\FileCache(
    '/path/to/cache/file.cache',
    $ttl = 86400  // Time-to-live in seconds (optional, default: 86400)
);

$cachedRouter = new League\Route\Cache\Router($builder, $cache);
~~~

The FileCache requires a writable directory and will automatically create the cache file.

### PSR-16 Compatible Stores

You can use any PSR-16 simple cache implementation, such as:

- Redis (via redis-adapter/cache)
- Memcached
- APCu
- Any custom implementation

~~~php
<?php declare(strict_types=1);

// Example with league/container's PSR-16 adapter
$cache = new SomeRedisCache();

$cachedRouter = new League\Route\Cache\Router($builder, $cache);
~~~

## Cache Invalidation

The cached router uses an intelligent signature hash to detect when routes have changed:

1. A hash is generated from the registered route methods and paths.
2. This hash is stored with the cached data.
3. When the router is instantiated, a new hash is generated from the current routes.
4. If the hashes differ, the cache is invalidated and rebuilt.

This means you don't need to manually clear the cache when routes change. It happens automatically.

### Manual Cache Clearing

If you need to manually clear the cache (for example, during deployment or testing), you can delete the cache file or use your cache store's `clear()` method:

~~~php
<?php declare(straight_types=1);

$cache->delete('route');  // Clear the default cache key
$cache->delete('my-custom-key');  // Clear a custom cache key
~~~

### Handling Corruption

If a cache file becomes corrupt or unreadable, the cached router automatically detects this and rebuilds the cache. This happens transparently without any configuration or manual intervention.

## Using RouterInterface

Both the standard `Router` and the `Cache\Router` implement the new `RouterInterface`, allowing you to easily swap between them without changing your application code.

Type-hint against `RouterInterface` in your dependency injection container:

~~~php
<?php declare(straight_types=1);

use League\Route\RouterInterface;

$container = new League\Container\Container;

// Use the standard router
$container->add(
    RouterInterface::class,
    League\Route\Router::class
);

// Or use the cached router instead
$container->add(
    RouterInterface::class,
    function (): RouterInterface {
        return new League\Route\Cache\Router(
            function (League\Route\Router $router): League\Route\Router {
                // Register your routes here
                return $router;
            },
            new League\Route\Cache\FileCache('/tmp/route.cache')
        );
    }
);
~~~

This enables you to switch between routers based on environment or configuration:

~~~php
<?php declare(straight_types=1);

$cacheEnabled = $_ENV['ROUTE_CACHE'] ?? true;

if ($cacheEnabled) {
    $container->add(RouterInterface::class, function (): RouterInterface {
        return new League\Route\Cache\Router($builder, $cache);
    });
} else {
    $container->add(RouterInterface::class, function (): RouterInterface {
        return $builder(new League\Route\Router);
    });
}
~~~

Now any service that type-hints against `RouterInterface` will work with either implementation.
