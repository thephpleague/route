---
layout: post
title: Upgrade Guide
sections:
    Upgrading to 7.0: upgrading-to-70
    PHP Version: php-version
    PHP 8.3 Language Features: php-83-language-features
    RouterInterface: routerinterface
    Route Matching: route-matching
    URL Generation: url-generation
    Middleware Groups: middleware-groups
    Route Freezing: route-freezing
    Matched Route in Middleware: matched-route-in-middleware
    MethodNotAllowedException: methodnotallowedexception
    Dispatcher Composition: dispatcher-composition
    OPTIONS Callable Changes: options-callable-changes
    Cached Router: cached-router
    setVars Changes: setvars-changes
    Removed Features: removed-features
---
## Upgrading to 7.0

This guide covers the breaking changes introduced in version 7.0 and what you need to do to upgrade.

## PHP Version

The minimum supported PHP version has been raised from 8.1 to **8.3**. PHP 8.2 has reached end-of-life and is no longer supported. Ensure your environment is running PHP 8.3 or later before upgrading.

## PHP 8.3 Language Features

Version 7.0 adopts several PHP 8.3 language features:

- **Typed class constants**: All class constants now have explicit type declarations. If you extend `Router` and redefine the `IDENTIFIER_SEPARATOR` constant, ensure your value is a `string`.
- **`#[\Override]` attributes**: Methods implementing interface contracts now use `#[\Override]` for compile-time safety. This is an internal change and does not affect your code unless you extend library classes and override the same methods.

## RouterInterface

A new `League\Route\RouterInterface` has been introduced that extends PSR-15's `RequestHandlerInterface`. Both `League\Route\Router` and `League\Route\Cache\Router` implement this interface.

If you have code that type-hints against `League\Route\Router` directly, consider updating to type-hint against `RouterInterface` instead to allow switching between implementations:

~~~php
<?php declare(strict_types=1);

use League\Route\RouterInterface;

function registerRoutes(RouterInterface $router): void
{
    $router->map('GET', '/', 'HomeController::index');
}
~~~

## Route Matching

A new `match()` method is available on both `Router` and `Cache\Router`. It returns a `MatchResult` value object containing a `MatchStatus` enum (`Found`, `NotFound`, `MethodNotAllowed`) without executing your handlers.

This is a purely additive change and requires no action unless you want to take advantage of it. See [Route Matching](/7.x/route-matching) for details.

## URL Generation

A new `UrlGeneratorInterface` provides reverse routing from named routes. Both `Router` and `Cache\Router` implement this interface.

~~~php
<?php declare(strict_types=1);

use League\Route\UrlGeneratorInterface;

$router = new League\Route\Router;

$router->get('/users/{id}', 'UserController::show')->setName('users.show');

$url = $router->generateUrl('users.show', ['id' => '42']);
// => /users/42
~~~

This is a purely additive change. See [Routes](/7.x/routes) for full documentation including query string parameters, optional segments, and error handling.

If you type-hint against `UrlGeneratorInterface` separately from `RouterInterface`, your code can generate URLs without depending on the full router.

`generateUrl()` now supports FastRoute optional segments (`[/{param}]`). Optional parameters are resolved from defaults set via `setVars()` or omitted entirely. See [Routes](/7.x/routes#url-generation) for details.

## Middleware Groups

Named middleware groups allow you to define a collection of middleware once and apply it by name across multiple routes or groups. See [Middleware](/7.x/middleware#middleware-groups) for full documentation.

~~~php
<?php declare(strict_types=1);

$router->defineMiddlewareGroup('api', [
    Acme\AuthMiddleware::class,
    Acme\ThrottleMiddleware::class,
]);

$router->group('/api', function ($group) {
    $group->get('/users', 'UserController::index');
})->middlewareGroup('api');
~~~

This is a purely additive change and requires no action unless you want to take advantage of it.

## Route Freezing

Routes are now frozen (made immutable) after `prepareRoutes()` completes. Calling configuration setters such as `setHost()`, `setScheme()`, `setPort()`, `setName()`, `setStrategy()`, `setVars()`, or `middleware()` on a frozen route throws `LogicException`.

**If you modify route objects after dispatch**, your code will now throw an exception instead of silently having no effect. Move any route configuration before the first call to `dispatch()` or `match()`.

`Route::setPathVars()` is exempted from freezing as it is an internal dispatcher operation. It is now marked `@internal` and should not be called by application code.

The `FreezeableInterface` and `FreezeableTrait` provide the immutability mechanism. If you extend `Route`, the freezing behaviour is inherited automatically.

## Matched Route in Middleware

The matched `Route` object is now added to the request attributes during dispatch, keyed by `Route::class`. Middleware can retrieve it to inspect the matched route for authorisation, logging, or analytics. See [Middleware](/7.x/middleware#matched-route-in-middleware) for examples.

This is a purely additive change. The route object in the attributes is frozen, so middleware cannot modify route configuration.

## MethodNotAllowedException

`MethodNotAllowedException` now exposes a `getAllowedMethods(): array` method that returns the allowed HTTP methods as a typed array. Previously, the allowed methods were only accessible by parsing the `Allow` header string from `getHeaders()`.

~~~php
<?php declare(strict_types=1);

try {
    $router->dispatch($request);
} catch (League\Route\Http\Exception\MethodNotAllowedException $e) {
    $allowed = $e->getAllowedMethods();
}
~~~

This is a purely additive change. The `Allow` header continues to be set as before.

## Dispatcher Composition

The internal `Dispatcher` class no longer extends `FastRoute\Dispatcher\GroupCountBased`. It now wraps the FastRoute dispatcher via composition.

**If you extended `League\Route\Dispatcher`**, your code will need updating:

- The class no longer inherits from `GroupCountBasedDispatcher`. Calls to `parent::dispatch()` will fail.
- The constructor now requires three arguments: `$routesData`, `$strategy`, and `$routeMap`. The old `setRouteMap()` method has been removed.
- `Dispatcher` no longer implements `RouteConditionHandlerInterface`. The `setHost()`, `setName()`, `setPort()`, and `setScheme()` methods are no longer available on the Dispatcher.

A new `DispatcherInterface` (marked `@internal`) has been introduced with `dispatchRequest()` and `matchRequest()`. This is an internal interface and may change without notice; do not implement it in your own code.

**If you only use the `Router` class** (the typical case), no changes are required.

## OPTIONS Callable Changes

The callable returned by `OptionsHandlerInterface::getOptionsCallable()` now receives `(ServerRequestInterface $request, array $vars)` when invoked. Previously, the `JsonStrategy` implementation ignored these parameters.

**If you have a custom strategy implementing `OptionsHandlerInterface`**, update the closure returned by `getOptionsCallable()` to accept these parameters:

~~~php
<?php declare(strict_types=1);

public function getOptionsCallable(array $methods): callable
{
    return function (ServerRequestInterface $request, array $vars) use ($methods): ResponseInterface {
        // $request is now available for CORS handling
        $origin = $request->getHeaderLine('Origin');
        // ...
    };
}
~~~

The `OptionsHandlerInterface` method signature itself has not changed. Only the contract of the returned callable has been tightened.

## Cached Router

The cached router has been completely redesigned. It now caches only the compiled FastRoute data (plain PHP arrays) rather than serialising the entire `Router` object. This means:

- `laravel/serializable-closure` is **no longer required** as a hard dependency. If you relied on it being pulled in transitively, add it to your own `composer.json`.
- Existing cache files from version 6.x are **not compatible** with the new format. Delete any existing cache files before deploying.
- The `FileCache::getMultiple()`, `setMultiple()`, and `deleteMultiple()` methods now throw `BadMethodCallException` instead of silently returning incorrect values.

## setVars Changes

`Route::setVars()` now sets declaration-time default variables only. Previously it was also used internally by the dispatcher to set path-matched variables, which could cause unexpected behaviour when default vars were overwritten.

If you called `setVars()` at dispatch time in a custom strategy or extension, use `setPathVars()` instead for dispatcher-set path parameters.

`Route::getVars()` continues to return the merged result of default vars and path vars, with path vars taking precedence. No changes are required if you only used `getVars()` in controllers or middleware.

## Removed Features

### PHP 8.1 and 8.2 support dropped

PHP 8.1 and 8.2 are no longer supported.

### laravel/serializable-closure removed from hard dependencies

The `laravel/serializable-closure` package has been moved from `require` to `suggest` in `composer.json`. It is no longer installed automatically. If your application serialises closures for caching purposes outside of Route, add the package to your own dependencies:

~~~
composer require laravel/serializable-closure
~~~

### Closure wrapping removed from Route constructor

The `Route` constructor no longer automatically wraps closure handlers. If you construct `Route` objects directly (rather than via `$router->map()`) and pass a closure as the handler, verify the closure is a valid callable without relying on automatic wrapping.

### Request-dependent route compilation removed

Routes are now compiled unconditionally, with condition matching (host, scheme, port) performed at dispatch time. This is an internal change and should not affect most applications. If you have a custom dispatcher or strategy that relied on the previous request-dependent filtering in `prepareRoutes()`, review your implementation against the new `Dispatcher` source.
