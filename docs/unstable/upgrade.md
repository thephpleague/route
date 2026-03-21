---
layout: post
title: Upgrade Guide
sections:
    Upgrading to 7.0: upgrading-to-70
    PHP Version: php-version
    PHP 8.3 Language Features: php-83-language-features
    RouterInterface: routerinterface
    Route Matching: route-matching
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

This is a purely additive change and requires no action unless you want to take advantage of it. See [Route Matching](/unstable/route-matching) for details.

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
