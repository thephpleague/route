---
layout: post
title: Route Matching
sections:
    Introduction: introduction
    RouterInterface: router-interface
    Matching Routes: matching-routes
    MatchResult: match-result
    Condition Matching: condition-matching
    Use Cases: use-cases
---
## Introduction

Version 7.0 introduces a powerful new `match()` method that allows you to check if a route matches without executing it. This enables non-blocking route matching for advanced use cases like conditional logic, authorisation checks, and route debugging.

The `match()` method returns a `MatchResult` value object containing information about the match, including a `MatchStatus` enum that indicates whether the route was found, not found, or if the method was not allowed.

## RouterInterface

The new `RouterInterface` extends PSR-15's `RequestHandlerInterface` and is implemented by both `Router` and `Cache\Router`. This interface enables type-safe dependency injection in your application.

~~~php
<?php declare(strict_types=1);

use League\Route\RouterInterface;

class SomeService
{
    public function __construct(private RouterInterface $router) {}
}
~~~

By type-hinting against the `RouterInterface` instead of a concrete class, you can easily swap between the standard `Router` and the cached `Cache\Router` without changing your application code.

~~~php
<?php declare(strict_types=1);

$container = new League\Container\Container;

$container->add(League\Route\RouterInterface::class, League\Route\Router::class);
~~~

The `Cache\Router` requires a builder callable and a cache store as constructor arguments, so it must be bound via a factory closure. See [Dependency Injection](/7.x/dependency-injection) for full examples.

The `RouterInterface` declares two methods and inherits one from `RequestHandlerInterface`:

- `dispatch(ServerRequestInterface $request): ResponseInterface` - Dispatch the request and return a response
- `match(ServerRequestInterface $request): MatchResult` - Match the request and return a result without executing
- `handle(ServerRequestInterface $request): ResponseInterface` - Inherited from PSR-15 `RequestHandlerInterface`

See [Dependency Injection](/7.x/dependency-injection) for examples of binding `RouterInterface` in a container.

## Matching Routes

The `match()` method allows you to determine if a route exists for a given request without executing the matched controller. This is useful for scenarios where you need to make decisions based on route availability before processing the request.

~~~php
<?php declare(strict_types=1);

use League\Route\Router;
use League\Route\MatchStatus;

$router = new Router;
$router->map('GET', '/users/{id}', 'UserController::show');
$router->map('POST', '/users', 'UserController::store');

$result = $router->match($request);

if ($result->isFound()) {
    $route = $result->getRoute();
    echo 'Matched route: ' . $route->getPath();
} elseif ($result->isConditionNotMet()) {
    $route = $result->getRoute();
    echo 'Route exists but conditions do not match: ' . $route->getHost();
} elseif ($result->isMethodNotAllowed()) {
    echo 'Method not allowed';
    echo 'Allowed methods: ' . implode(', ', $result->getAllowedMethods());
} else {
    echo 'Route not found';
}
~~~

## MatchResult

The `MatchResult` value object contains the result of a route match operation. It provides convenient methods to determine the match status:

### Properties and Methods

- `isFound(): bool` - Returns true if a route was found and all conditions matched
- `isMethodNotAllowed(): bool` - Returns true if a route matched the path but the HTTP method is not allowed
- `isConditionNotMet(): bool` - Returns true if a route matched the path and method but a host, scheme, or port condition failed
- `getRoute(): Route` - Returns the matched `Route` object; available for `Found` and `ConditionNotMet` results, throws `\LogicException` otherwise
- `getAllowedMethods(): array` - Returns the allowed HTTP methods; throws `\LogicException` if the result is not `MethodNotAllowed`
- `getStatus(): MatchStatus` - Returns the `MatchStatus` enum value (`Found`, `NotFound`, `MethodNotAllowed`, `ConditionNotMet`)

~~~php
<?php declare(strict_types=1);

use League\Route\Router;
use League\Route\MatchStatus;

$router = new Router;
$router->get('/users/{id}', 'UserController::show');

$result = $router->match($request);

match ($result->getStatus()) {
    MatchStatus::Found => handleFound($result->getRoute()),
    MatchStatus::MethodNotAllowed => handleMethodNotAllowed($result->getAllowedMethods()),
    MatchStatus::ConditionNotMet => handleConditionNotMet($result->getRoute()),
    MatchStatus::NotFound => handleNotFound(),
};
~~~

## Condition Matching

When a route matches by path and HTTP method but fails a host, scheme, or port condition, the `match()` method returns a `ConditionNotMet` result instead of `NotFound`. This distinction helps with debugging: you can tell whether a 404 means "no route exists" or "the route exists but you are accessing it from the wrong host or scheme".

~~~php
<?php declare(strict_types=1);

use League\Route\Router;
use League\Route\MatchStatus;

$router = new Router;
$router->get('/api/users', 'UserController::index')->setHost('api.example.com');

$result = $router->match($request);

if ($result->isConditionNotMet()) {
    $route = $result->getRoute();
    echo 'Route exists at ' . $route->getPath() . ' but conditions do not match';
    echo 'Expected host: ' . $route->getHost();
}
~~~

During dispatch, `ConditionNotMet` is handled identically to `NotFound` for backwards compatibility (a 404 response or `NotFoundException` is produced). The distinction is only visible through the `match()` API.

## Use Cases

### Authorisation Checks

Check if a user has permission to access a route before dispatching:

~~~php
<?php declare(strict_types=1);

use League\Route\Router;

$router = new Router;
// ... register routes

$result = $router->match($request);

if (!$result->isFound()) {
    return sendNotFoundResponse();
}

if (!$this->userCanAccess($result->getRoute())) {
    return sendForbiddenResponse();
}

return $router->dispatch($request);
~~~

### Route Debugging

Log or debug information about matched routes during development:

~~~php
<?php declare(strict_types=1);

$result = $router->match($request);

if ($result->isFound()) {
    $route = $result->getRoute();
    error_log('Matched: ' . $route->getMethod() . ' ' . $route->getPath());
    error_log('Variables: ' . json_encode($route->getVars()));
}
~~~

### Conditional Logic

Make decisions based on whether a route exists:

~~~php
<?php declare(strict_types=1);

$adminResult = $router->match($adminRequest);
$publicResult = $router->match($publicRequest);

if ($adminResult->isFound() && $publicResult->isFound()) {
    echo 'Both admin and public routes match';
}
~~~

### Route Introspection

Build route maps or documentation by iterating routes and matching them:

~~~php
<?php declare(strict_types=1);

foreach ($router->getRoutes() as $route) {
    // Create a fake request for matching
    $fakeRequest = $requestFactory->createServerRequest($route->getMethod(), $route->getPath());
    $result = $router->match($fakeRequest);

    if ($result->isFound()) {
        echo sprintf(
            '%s %s - %s' . PHP_EOL,
            $route->getMethod(),
            $route->getPath(),
            $route->getName() ?? 'Unnamed'
        );
    }
}
~~~
