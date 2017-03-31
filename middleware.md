---
layout: default
permalink: /middleware/
title: Middleware
---

# Middleware

Middleware allows you to execute code before and after your controller is invoked.

Route allows you to add a PSR-7 compatible callable to the stack that would be invoked every time your app runs or specifically when a route is matched or a route that is part of a group is matched.

The signature for a PSR-7 compatible middleware callable looks like this.

~~~php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$middleware = function (ServerRequestInterface $request, ResponseInterface $response, callable $next) {
    // $request is a PSR-7 request implementation
    // $response is a PSR-7 response implementation
    // $next is the next callable in the stack
};
~~~

Each middleware SHOULD either invoke the `$next` callable passing the `$request` and `$response` to it or return the `$response` to cut the execution chain short and end the process here.

## Types of Middleware

A middleware can be any type of callable.

### Closure

~~~php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$middleware = function (ServerRequestInterface $request, ResponseInterface $response, callable $next) {
        $response->getBody()->write('This will run before your controller.');
        $response = $next($request, $response);
        $response->getBody()->write('This will run after your controller.');

        return $response;
};
~~~

### Invokable Class

A class that implements the magic `__invoke` method.

~~~php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ExampleMiddleware
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        // ...
        return $next($request, $response);
    }
}

$middleware = new ExampleMiddleware;
~~~

### Class Method Callable

~~~php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ExampleClass
{
    public function exampleMiddleware(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        // ...
        return $next($request, $response);
    }
}

$middleware = [new ExampleClass, 'exampleMiddleware'];
~~~

## Adding Middleware

Middleware can be added to run every time the app runs, only when a specific route is matched, or when a route is matched that belongs to a group.

### App

~~~php
<?php

use League\Route\RouteCollection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$router = new RouteCollection;

$router->middleware(function (ServerRequestInterface $request, ResponseInterface $response, callable $next) {
    // ...
    return $response;
});

$router->get('/some-route', function (ServerRequestInterface $request, ResponseInterface $response) {
    // ...
    return $response;
});
~~~

### Route Specific

~~~php
<?php

use League\Route\RouteCollection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$router = new RouteCollection;

$router
    ->get('/some-route', function (ServerRequestInterface $request, ResponseInterface $response) {
        // ...
        return $response;
    })
    ->middleware(function (ServerRequestInterface $request, ResponseInterface $response, callable $next) {
        // ...
        return $response;
    })
;
~~~

### Group Specific

~~~php
<?php

use League\Route\RouteCollection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$router = new RouteCollection;

$router->group('/prefix', function ($router) {
    $router->get('/some-route', function (ServerRequestInterface $request, ResponseInterface $response) {
        // ...
        return $response;
    });
})->middleware(function (ServerRequestInterface $request, ResponseInterface $response, callable $next) {
    // ...
    return $response;
});
~~~

## RouteCollection as middleware

You can use the RouteCollection as a middleware in a different stack of middleware. Because the `dispatch` method has the same interface as a callable middleware, you can combine it into an array format of a PHP callable.

~~~php
<?php
$middleware = [$router, 'dispatch'];
~~~
