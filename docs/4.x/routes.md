---
layout: post
title: Routes
sections:
    Request Verbs: request-verbs
    Route Conditions: route-conditions
    Route Groups: route-groups
    Wildcard Routes: wildcard-routes
---
## Request Verbs

Route has convenience methods for setting routes that will respond differently depending on the HTTP request method.

~~~php
<?php declare(strict_types=1);

$router = new League\Route\Router;

$router->get('/acme/route', 'Acme\Controller::getMethod');
$router->post('/acme/route', 'Acme\Controller::postMethod');
$router->put('/acme/route', 'Acme\Controller::putMethod');
$router->patch('/acme/route', 'Acme\Controller::patchMethod');
$router->delete('/acme/route', 'Acme\Controller::deleteMethod');
$router->head('/acme/route', 'Acme\Controller::headMethod');
$router->options('/acme/route', 'Acme\Controller::optionsMethod');
~~~

Each of the above routes will respond to the same URI but will invoke a different callable based on the HTTP request method.

## Route Conditions

There are times when you may wish to add further conditions to route matching other than the request verb and URI. Route allows this by chaining further conditions to the route definition.

### Host

You can limit a route to match only if the host is a match as well as the request verb and URI.

~~~php
<?php declare(strict_types=1);

$router = new League\Route\Router;

$router
    ->map('GET', '/acme/route', 'Acme\Controller::getMethod')
    ->setHost('example.com')
;
~~~

The route above will only match if the request is for `GET //example.com/acme/route`.

### Scheme

You can limit a route to match only if the scheme is a match as well as the request verb and URI.

~~~php
<?php declare(strict_types=1);

$router = new League\Route\Router;

$router
    ->map('GET', '/acme/route', 'Acme\Controller::getMethod')
    ->setHost('example.com')
    ->setScheme('https')
;
~~~

The route above will only match if the request is for `GET https://example.com/acme/route`.

### Port

You can limit a route to match only if the port is a match as well as the request verb and URI.

~~~php
<?php declare(strict_types=1);

$router = new League\Route\Router;

$router
    ->map('GET', '/acme/route', 'Acme\Controller::getMethod')
    ->setHost('example.com')
    ->setScheme('https')
    ->setPort(8080)
;
~~~

The route above will only match if the request is for `GET https://example.com:8080/acme/route`.

As you can see above, these conditions are chainable. You can also apply the conditions to a route group so that they will be applied to all routes defined in that group, or individually on any of the routes defined within a group. For more on this, see below.

## Route Groups

Route groups are a way of organising your route definitions, they allow us to provide conditions and a prefix across multiple routes. As an example, this would be useful for an admin area of a website.

~~~php
<?php declare(strict_types=1);

$router = new League\Route\Router;

$router->group('/admin', function ($route) {
    $router->map('GET', '/acme/route1', 'AcmeController::actionOne');
    $router->map('GET', '/acme/route2', 'AcmeController::actionTwo');
    $router->map('GET', '/acme/route3', 'AcmeController::actionThree');
});
~~~

The above code will define routes that will respond to the following.

~~~shell
GET /admin/acme/route1
GET /admin/acme/route2
GET /admin/acme/route3
~~~

### Conditions

As mentioned above, route conditions can be applied to a group and will be matched across all routes contained in that group, specific routes within the group can override this functionality as displayed below.

~~~php
<?php declare(strict_types=1);

$router = new League\Route\Router;

$router->group('/admin', function ($route) {
    $router->map('GET', '/acme/route1', 'AcmeController::actionOne');
    $router->map('GET', '/acme/route2', 'AcmeController::actionTwo')->setScheme('https');
    $router->map('GET', '/acme/route3', 'AcmeController::actionThree');
})
    ->setScheme('http')
    ->setHost('example.com')
;
~~~

The above code will define routes that will respond to the following.

~~~shell
GET http://example.com/admin/acme/route1
GET https://example.com/admin/acme/route2
GET http://example.com/admin/acme/route3
~~~

## Wildcard Routes

Wildcard routes allow a route to respond to dynamic segments of a URI. If a route has dynamic URI segments, they will be passed in to the controller as an associative array of arguments.

~~~php
<?php declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$router = new League\Route\Router;

$router->map('GET', '/user/{id}/{name}', function (ServerRequestInterface $request, array $args) : ResponseInterface {
    // $args = [
    //     'id'   => {id},  // the actual value of {id}
    //     'name' => {name} // the actual value of {name}
    // ];

    // ...
});
~~~

Dynamic URI segments can also be limited to match certain requirements.

~~~php
<?php declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$router = new League\Route\Router;

// this route will only match if {id} is numeric and {name} is a alpha
$router->map('GET', '/user/{id:number}/{name:word}', function (ServerRequestInterface $request, array $args) : ResponseInterface {
    // $args = [
    //     'id'   => {id},  // the actual value of {id}
    //     'name' => {name} // the actual value of {name}
    // ];

    // ...
});
~~~

There are several built in conditions for dynamic segments of a URI.

- number
- word
- alphanum_dash
- slug
- uuid

Dynamic segments can also be set as any regular expression such as {id:[0-9]+}.

For convenience, you can also register your own aliases for a particular regular expression using the `addPatternMatcher` method on `League\Route\Router`.

For example:

~~~php
<?php declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$router = new League\Route\Router;

$router->addPatternMatcher('wordStartsWithM', '(m|M)[a-zA-Z]+');

$router->map('GET', 'user/mTeam/{name:wordStartsWithM}', function (
    ServerRequestInterface $request,
    array $args
) : ResponseInterface {
    // $args = [
    //     'id'   => {id},  // the actual value of {id}
    //     'name' => {name} // the actual value of {name}
    // ];

    // ...
});
~~~

The above pattern matcher will create an internal regular expression string: `{$1:(m|M)[a-zA-Z]+}`, where `$1` will interpret to `name`, the variable listed before the colon.
