---
layout: post
title: Routes
sections:
    Request Verbs: request-verbs
    Route Conditions: route-conditions
    Route Groups: route-groups
    Wildcard Routes: wildcard-routes
    URL Generation: url-generation
    Route Introspection: route-introspection
    Default Route Variables: default-route-variables
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

$router->group('/admin', function (\League\Route\RouteGroup $route) {
    $route->map('GET', '/acme/route1', 'AcmeController::actionOne');
    $route->map('GET', '/acme/route2', 'AcmeController::actionTwo');
    $route->map('GET', '/acme/route3', 'AcmeController::actionThree');
});
~~~

The above code will define routes that will respond to the following.

~~~shell
GET /admin/acme/route1
GET /admin/acme/route2
GET /admin/acme/route3
~~~

### Named Routes

Named routes helps when you want to retrieve a Route by a human friendly label.

~~~php
<?php declare(strict_types=1);

$router = new League\Route\Router;

$router->group('/admin', function (\League\Route\RouteGroup $route) {
    $route->map('GET', '/acme/route1', 'AcmeController::actionOne')->setName('actionOne');
    $route->map('GET', '/acme/route2', 'AcmeController::actionTwo')->setName('actionTwo');
});

$route = $router->getNamedRoute('actionOne');
$route->getPath(); // "/admin/acme/route1"
~~~

### Conditions

As mentioned above, route conditions can be applied to a group and will be matched across all routes contained in that group, specific routes within the group can override this functionality as displayed below.

~~~php
<?php declare(strict_types=1);

$router = new League\Route\Router;

$router->group('/admin', function (\League\Route\RouteGroup $route) {
    $route->map('GET', '/acme/route1', 'AcmeController::actionOne');
    $route->map('GET', '/acme/route2', 'AcmeController::actionTwo')->setScheme('https');
    $route->map('GET', '/acme/route3', 'AcmeController::actionThree');
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

$router->map('GET', '/user/{id}/{name}', function (ServerRequestInterface $request, array $args): ResponseInterface {
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

$router->map('GET', '/user/{id:number}/{name:word}', function (ServerRequestInterface $request, array $args): ResponseInterface {
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
): ResponseInterface {
    // $args = [
    //     'id'   => {id},  // the actual value of {id}
    //     'name' => {name} // the actual value of {name}
    // ];

    // ...
});
~~~

The above pattern matcher will create an internal regular expression string: `{$1:(m|M)[a-zA-Z]+}`, where `$1` will interpret to `name`, the variable listed before the colon.

## URL Generation

Named routes can be used to generate URLs, the inverse of route matching. Both `Router` and `Cache\Router` implement `UrlGeneratorInterface`.

~~~php
<?php declare(strict_types=1);

$router = new League\Route\Router;

$router->get('/users/{id}', 'UserController::show')->setName('users.show');
$router->get('/posts/{slug:slug}', 'PostController::show')->setName('posts.show');

$url = $router->generateUrl('users.show', ['id' => '42']);
// => /users/42

$url = $router->generateUrl('posts.show', ['slug' => 'hello-world']);
// => /hello-world
~~~

### Optional Segments

Routes can include optional segments using FastRoute's bracket syntax `[/{param}]`. The `generateUrl()` method handles these correctly: if the parameter is provided, the segment is included; if not, it is omitted or the default from `setVars()` is used.

~~~php
<?php declare(strict_types=1);

$router = new League\Route\Router;

$router->get('/blog[/{page:number}]', 'BlogController::index')
    ->setName('blog.index')
    ->setVars(['page' => '1']);

$router->generateUrl('blog.index');
// => /blog/1 (uses default from setVars)

$router->generateUrl('blog.index', ['page' => '3']);
// => /blog/3 (substitution overrides default)
~~~

If no default is set and no substitution is provided, the optional segment is omitted entirely:

~~~php
<?php declare(strict_types=1);

$router->get('/blog[/{page:number}]', 'BlogController::index')
    ->setName('blog.index');

$router->generateUrl('blog.index');
// => /blog (optional segment omitted)
~~~

Nested optional segments are also supported:

~~~php
<?php declare(strict_types=1);

$router->get('/archive[/{year}[/{month}]]', 'ArchiveController::index')
    ->setName('archive');

$router->generateUrl('archive', ['year' => '2024']);
// => /archive/2024

$router->generateUrl('archive', ['year' => '2024', 'month' => '03']);
// => /archive/2024/03

$router->generateUrl('archive');
// => /archive
~~~

Required parameters (outside brackets) still throw `InvalidArgumentException` if missing, even when optional segments are present.

### Missing Parameters

If required parameters are not provided, an `InvalidArgumentException` is thrown with a message listing the missing parameters.

~~~php
<?php declare(strict_types=1);

$router->get('/users/{id}/posts/{postId}', 'PostController::show')->setName('user.posts.show');

$router->generateUrl('user.posts.show', ['id' => '42']);
// throws InvalidArgumentException: Missing required parameters {postId} for route "user.posts.show"
~~~

### Extra Parameters as Query String

Any substitution keys that do not match a route parameter are appended as a query string.

~~~php
<?php declare(strict_types=1);

$router->get('/users/{id}', 'UserController::show')->setName('users.show');

$url = $router->generateUrl('users.show', ['id' => '42', 'page' => '2', 'sort' => 'name']);
// => /users/42?page=2&sort=name
~~~

### Type-hinting

If you need URL generation without a full router dependency, type-hint against `UrlGeneratorInterface`:

~~~php
<?php declare(strict_types=1);

use League\Route\UrlGeneratorInterface;

class NavigationBuilder
{
    public function __construct(private UrlGeneratorInterface $urlGenerator) {}

    public function buildMenu(): array
    {
        return [
            'home' => $this->urlGenerator->generateUrl('home'),
            'profile' => $this->urlGenerator->generateUrl('profile', ['id' => '1']),
        ];
    }
}
~~~

## Route Introspection

You can retrieve all routes registered on the router using the `getRoutes()` method. This returns an array of all registered `Route` objects, including those defined within route groups. This is useful for route debugging, documentation generation, and advanced routing scenarios.

~~~php
<?php declare(strict_types=1);

$router = new League\Route\Router;

$router->get('/users', 'UserController::index');
$router->post('/users', 'UserController::store');
$router->get('/users/{id}', 'UserController::show');

$routes = $router->getRoutes();

foreach ($routes as $route) {
    echo $route->getMethod() . ' ' . $route->getPath() . PHP_EOL;
}
~~~

Each `Route` object provides methods to inspect its configuration:

- `getMethod()` - the HTTP method(s)
- `getPath()` - the route path pattern
- `getName()` - the route name (if set)
- `getHost()` - the route host condition (if set)
- `getScheme()` - the route scheme condition (if set)
- `getPort()` - the route port condition (if set)
- `getVars()` - the route variables (merged defaults and path variables)

## Default Route Variables

Routes can have default variables set that will be merged with any path variables captured during route matching. This is useful for providing context or permissions to your controllers.

~~~php
<?php declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$router = new League\Route\Router;

$router->map('GET', '/users/{id}', function (ServerRequestInterface $request, array $args): ResponseInterface {
    $userId = $args['id'];
    $userRole = $args['role'];
    // ...
})
    ->setVars(['role' => 'viewer']);
~~~

When this route is matched, the `$args` array passed to the controller will contain both the default variable and the path variable:

~~~php
[
    'role' => 'viewer',  // from setVars()
    'id' => '42'         // from path matching {id}
]
~~~

This allows you to separate configuration defaults from dynamic route parameters, providing cleaner, more maintainable code.
