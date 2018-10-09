---
layout: post
title: Concepts
sections:
    Wildcard Routes: wildcard-routes
    Request Verbs: request-verbs
    Route Conditions: route-conditions
    Route Groups: route-groups
    Controllers: controllers
    Middleware: middleware
---
## Wildcard Routes

Wildcard routes allow a route to respond to dynamic parts of a URI. If a route has dynamic parts, they will be passed in to the controller as an associative array of arguments.

~~~php
<?php

$router = new League\Route\RouteCollection;

$router->map('GET', '/user/{id}/{name}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) {
    // $args = [
    //     'id'   => {id},  // the actual value of {id}
    //     'name' => {name} // the actual value of {name}
    // ];

    return $response;
});
~~~

Dynamic parts of a URI can also be limited to match certain requirements.

~~~php
<?php

$router = new League\Route\RouteCollection;

// this route will only match if {id} is numeric and {name} is a alpha
$router->map('GET', '/user/{id:number}/{name:word}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) {
    // $args = [
    //     'id'   => {id},  // the actual value of {id}
    //     'name' => {name} // the actual value of {name}
    // ];

    return $response;
});
~~~

There are several built in conditions for dynamic parts of a URI.

- `number`
- `word`
- `alphanum_dash`
- `slug`
- `uuid`

Dynamic parts can also be set as any regular expression such as `{id:[0-9]+}`.

For convenience, you can also register your own aliases for a particular regular expression using the `addPatternMatcher` method on `RouteCollection`. For example:

~~~php
<?php

$router = new League\Route\RouteCollection;

$router->addPatternMatcher('wordStartsWithM', '(m|M)[a-zA-Z]+');

$router->map('GET', 'user/mTeam/{name:wordStartsWithM}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) {
    // $args = [
    //     'id'   => {id},  // the actual value of {id}
    //     'name' => {name} // the actual value of {name}
    // ];

    return $response;
});
~~~

The above pattern matcher will create an internal regular expression string: `{$1:(m|M)[a-zA-Z]+}`, where `$1` will interpret to 'name', the variable listed before the colon.

## Request Verbs

Route has convenience methods for setting routes that will respond differently depending on the HTTP request method.

~~~php
$route = new League\Route\RouteCollection;

$route->get('/acme/route', 'Acme\Controller::getMethod');
$route->post('/acme/route', 'Acme\Controller::postMethod');
$route->put('/acme/route', 'Acme\Controller::putMethod');
$route->patch('/acme/route', 'Acme\Controller::patchMethod');
$route->delete('/acme/route', 'Acme\Controller::deleteMethod');
$route->head('/acme/route', 'Acme\Controller::headMethod');
$route->options('/acme/route', 'Acme\Controller::optionsMethod');
~~~

Each of the above routes will respond to the same URI but will invoke a different callable based on the HTTP request method.

## Route Conditions

There are times when you may wish to add further conditions to route matching other than the request verb and URI. Route allows this by chaining further conditions to the route definition.

~~~php
<?php

$route = new League\Route\RouteCollection;

// this route will respond to http://example.com/acme/route
// or https://example.com/acme/route
$route->map('GET', '/acme/route', 'AcmeController::method')->setHost('example.com');

// this route will only respond to https://example.com/acme/route
$route->map('GET', '/acme/route', 'AcmeController::method')->setScheme('https')->setHost('example.com');
~~~

Conditions can also be applied across [route groups](#route-groups).

## Route Groups

Route groups are a way of organising your route definitions, they allow us to provide conditions and a prefix across multiple routes. As an example, this would be useful for an admin area of a website.

~~~php
<?php

$route = new League\Route\RouteCollection;

$route->group('/admin', function ($route) {
    $route->map('GET', '/acme/route1', 'AcmeController::actionOne');
    $route->map('GET', '/acme/route2', 'AcmeController::actionTwo');
    $route->map('GET', '/acme/route3', 'AcmeController::actionThree');
});
~~~

The above code will define the following routes.

~~~
GET /admin/acme/route1
GET /admin/acme/route2
GET /admin/acme/route3
~~~

Route [conditions](#route-conditions) can be applied to a group and will be matched across all routes contained in that group, specific routes within the group can override this functionality as displayed below.

~~~php
<?php

$route = new League\Route\RouteCollection;

$route->group('/admin', function ($route) {
    $route->map('GET', '/acme/route1', 'AcmeController::actionOne');
    $route->map('GET', '/acme/route2', 'AcmeController::actionTwo')->setScheme('https');
    $route->map('GET', '/acme/route3', 'AcmeController::actionThree');
})
    ->setScheme('http')
    ->setHost('example.com')
;
~~~

The above code will define the following routes.

~~~
GET http://example.com/admin/acme/route1
GET https://example.com/admin/acme/route2
GET http://example.com/admin/acme/route3
~~~

## Controllers

Route will dispatch to any `callable` as a controller. Below are some examples.

### Class methods

~~~php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AcmeController
{
    public function someMethod(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $response;
    }
}
~~~

The class method above can be defined as a controller in a couple of ways.

~~~php
<?php

$route = new League\Route\RouteCollection;

$route->map('GET', '/acme/route', 'AcmeController::someMethod');
$route->map('GET', '/acme/route', [new AcmeController, 'someMethod']);
~~~

### Magic __invoke

~~~php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AcmeController
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $response;
    }
}
~~~

~~~php
<?php

$route = new League\Route\RouteCollection;

$route->map('GET', '/acme/route', new AcmeController);
~~~

### Anonymous functions

~~~php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$route = new League\Route\RouteCollection;

$route->map('GET', '/acme/route', function (ServerRequestInterface $request, ResponseInterface $response) {
    return $response;
});
~~~

### Named functions

~~~php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$route = new League\Route\RouteCollection;

function controller(ServerRequestInterface $request, ResponseInterface $response) {
    return $response;
}

$route->map('GET', '/acme/route', 'controller');
~~~

## Middleware

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

### Types of Middleware

A middleware can be any type of callable.

#### Closure

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

#### Invokable Class

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

#### Class Method Callable

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

### Adding Middleware

Middleware can be added to run every time the app runs, only when a specific route is matched, or when a route is matched that belongs to a group.

#### App

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

#### Route Specific

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

#### Group Specific

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

### RouteCollection as middleware

You can use the RouteCollection as a middleware in a different stack of middleware. Because the `dispatch` method has the same interface as a callable middleware, you can combine it into an array format of a PHP callable.

~~~php
<?php
$middleware = [$router, 'dispatch'];
~~~
