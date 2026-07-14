---
layout: post
title: Middleware
sections:
    Introduction: introduction
    Example Middleware: example-middleware
    Defining Middleware: defining-middleware
    Lazy Middleware: lazy-middleware
    Middleware Groups: middleware-groups
    Middleware Order: middleware-order
    Matched Route in Middleware: matched-route-in-middleware
    Route as a Middleware: route-as-a-middleware
---
## Introduction

A middleware component is an individual component participating, often together with other middleware components, in the processing of an incoming request and the creation of a resulting response, as defined by PSR-7.

A middleware component may create and return a response without delegating to a request handler, if sufficient conditions are met.

Route is a [PSR-15](https://www.php-fig.org/psr/psr-15/) server request handler, and as such can handle the invocation of a stack of middlewares.

## Example Middleware

A good example of middleware is using one to determine user auth.

~~~php
<?php declare(strict_types=1);

namespace Acme;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\RedirectResponse;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($auth === true) {
            return $handler->handle($request);
        }

        return new RedirectResponse(/* .. */);
    }
}
~~~

## Defining Middleware

Middleware can be defined to run in 3 ways:

1. On the router - will run for every matched route.
2. On a route group - will run for any matched route in that group.
3. On a specific route - will run only when that route is matched.

Using the example middleware above, we can lock down the entire application by adding the middleware to the router.

~~~php
<?php declare(strict_types=1);

$router = new League\Route\Router;
$router->middleware(new Acme\AuthMiddleware);

// ... add routes
~~~

If we only want to lock down a group, such as an admin area, we can just add the middleware to the group.

~~~php
<?php declare(strict_types=1);

$router = new League\Route\Router;

$router
    ->group('/admin', function ($router) {
        // ... add routes
    })
    ->middleware(new Acme\AuthMiddleware)
;
~~~

Or finally, we can lock down a specific route by only adding the middleware to that route.

~~~php
<?php declare(strict_types=1);

$router = new League\Route\Router;

$router
    ->map('GET', '/private', 'Acme\SomeController::someMethod')
    ->middleware(new Acme\AuthMiddleware)
;
~~~

## Lazy Middleware

If you are using a PSR-11 dependency injection container, you can register middleware by class name using `lazyMiddleware()`. The middleware will be resolved from the container (or instantiated directly) at dispatch time, rather than upfront:

~~~php
<?php declare(strict_types=1);

$router = new League\Route\Router;

$router->lazyMiddleware(Acme\AuthMiddleware::class);

// Or add multiple at once
$router->lazyMiddlewares([
    Acme\AuthMiddleware::class,
    Acme\LoggingMiddleware::class,
]);
~~~

You can also prepend a lazy middleware to the front of the stack:

~~~php
<?php declare(strict_types=1);

$router->lazyPrependMiddleware(Acme\AuthMiddleware::class);
~~~

These lazy variants are available on the router, route groups, and individual routes, mirroring the eager `middleware()` methods.

## Middleware Groups

Middleware groups allow you to define a named collection of middleware that can be applied by name to routes, groups, or the router itself. This avoids repeating the same middleware list across multiple route definitions.

~~~php
<?php declare(strict_types=1);

$router = new League\Route\Router;

$router->defineMiddlewareGroup('api', [
    Acme\AuthMiddleware::class,
    Acme\ThrottleMiddleware::class,
    Acme\CorsMiddleware::class,
]);

$router->defineMiddlewareGroup('web', [
    Acme\SessionMiddleware::class,
    Acme\CsrfMiddleware::class,
]);
~~~

Once defined, apply a group by name to a route group or the router:

~~~php
<?php declare(strict_types=1);

$router
    ->group('/api/v1', function ($group) {
        $group->get('/users', 'UserController::index');
        $group->post('/users', 'UserController::store');
    })
    ->middlewareGroup('api')
;

$router
    ->group('/dashboard', function ($group) {
        $group->get('/', 'DashboardController::index');
    })
    ->middlewareGroup('web')
;
~~~

You can also apply multiple groups to the same route group:

~~~php
<?php declare(strict_types=1);

$router
    ->group('/admin/api', function ($group) {
        $group->get('/stats', 'AdminApiController::stats');
    })
    ->middlewareGroup('api')
    ->middlewareGroup('web')
;
~~~

Or apply a group globally to the router:

~~~php
<?php declare(strict_types=1);

$router->middlewareGroup('web');
~~~

Groups are expanded into the middleware stack at `prepareRoutes()` time. If a group name is undefined, an `InvalidArgumentException` is thrown during route preparation, not at definition time.

## Middleware Order

Middleware is invoked in a specific order but depending on the logic contained in a middleware, you can control whether your code is run before or after your controller is invoked.

The invocation order is as follows:

1. Exception handler defined by the active strategy. This middleware should wrap the rest of the application and catch any exceptions to be gracefully handled.
2. Middleware added to the router.
3. Middleware added to a matched route group.
4. Middleware added to a specific matched route.

To control whether your logic runs before or after your controller, you can have the request handler run as the first thing you do in your middleware, it will return a response, you can then do whatever you need to with the response and return it.

~~~php
<?php declare(strict_types=1);

namespace Acme;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SomeMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        // ...
        return $response;
    }
}
~~~

## Matched Route in Middleware

When a route is matched during dispatch, the `Route` object is added to the request attributes using `Route::class` as the key. This allows middleware to inspect the matched route for authorisation, logging, or analytics.

~~~php
<?php declare(strict_types=1);

namespace Acme;

use League\Route\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteAwareMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $request->getAttribute(Route::class);

        if ($route instanceof Route) {
            $routeName = $route->getName();
            $routePath = $route->getPath();
        }

        return $handler->handle($request);
    }
}
~~~

The route object is frozen after compilation, so middleware cannot modify route configuration.

## Route as a Middleware

`League\Route\Router` implements `League\Route\RouterInterface`, which extends PSR-15's `RequestHandlerInterface`. This means an instance of `League\Route\Router` can be added to any existing middleware stack as a request handler.
