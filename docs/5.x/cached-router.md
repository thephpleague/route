---
layout: post
title: Cached Router (BETA)
sections:
    Introduction: introduction
    BETA Notes: beta-notes
    To-do: to-do
    Usage: usage
    Cache Stores: cache-stores
---
> **Note:** The cached router implementation is currently in BETA and is not recommended for production applications without thorough testing.

## Introduction

Route provides a way to improve performance on larger applications by caching a serialised, fully configured router, minimising the amount of bootstrap code that is executed on each request.

## BETA Notes

A cached router is essentially a fully configured router object, serialised, and stored in a simple cache store. While this works well in test scenarios, depending on the controllers you add to the router, it is actually possible that the cache will attempt to serialise your entire application and cause side effects to your code depending on any custom magic methods you may be implementing.

It is recommended that when using a cached router, you lazy load your controllers. This way, they will not be instantiated/invoked until they are used.

Please report any issues you via an issue on the repository.

Why is this BETA feature included? To encourage higher rates of testing to make the feature as stable as possible.

## To-do

- &#10003; Provide a way to create a router, build it and cache the resulting object.
- &#10003; Have cached router accept any implementation of PSR-16 simple cache interface.
    - &#10003; Provide file based implementation.
- Test test test test test.
- Suggestions? Open tickets on repository.

## Usage

Usage of the cached router is very similar to usage of the standard route, but rather than instantiating and configuring `League\Route\Router`, you instead instantiate a cached router with a `callable` that will be used to configure the router.

~~~php
<?php declare(strict_types=1);

include 'path/to/vendor/autoload.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$cacheStore = new League\Route\Cache\FileCache('/path/to/cache/file.cache', $ttl = 86400);

$cachedRouter = new League\Route\Cache\Router(function (League\Route\Router $router) {
    // map a route
    $router->map('GET', '/', function (ServerRequestInterface $request): ResponseInterface {
        $response = new Laminas\Diactoros\Response;
        $response->getBody()->write('<h1>Hello, World!</h1>');
        return $response;
    });
    
    return $router;
}, $cacheStore);

$request = Laminas\Diactoros\ServerRequestFactory::fromGlobals(
    $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES
);

$response = $cachedRouter->dispatch($request);

// send the response to the browser
(new Laminas\HttpHandlerRunner\Emitter\SapiEmitter)->emit($response);
~~~

In the example above, if the file `/path/to/cache/file.cache` does not exist, the `callable` passed to the cached router will be invoked, and the returned router will be serialised and cached.

On subsequent requests, the router will be resolved from the cache file instead.

## Cache Stores

The cached router can use any [PSR-16](https://www.php-fig.org/psr/psr-16/) simple cache implementation, serialisation happens before it is passed to be stored in the cache, so the implementation will always only use one namespaced key, with the value being the serialised router.
