# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

### Added
- `RouterInterface` extending PSR-15 `RequestHandlerInterface` for Router/Cache\Router substitutability.
- `MatchResult` value object and `MatchStatus` enum for matching routes without dispatching (#328, #352).
- `Router::match()` method to inspect route matching without executing handlers.
- `Router::getRoutes()` method to introspect all registered routes (#330).
- `Route::setPathVars()` for dispatcher-set path parameters, separate from user-set defaults.
- Index-based route caching with signature hash validation for cache integrity.
- Corrupt cache auto-recovery in cached router.
- `UrlGeneratorInterface` with `generateUrl()` for reverse routing from named routes (#355).
- `Router` and `Cache\Router` both implement `UrlGeneratorInterface`.
- `DispatcherInterface` (marked `@internal`) formalising the Router-Dispatcher contract (#359).
- Middleware groups: `defineMiddlewareGroup()` and `middlewareGroup()` for named middleware collections on `Router` and `RouteGroup`.
- `MatchStatus::ConditionNotMet` enum case distinguishing host/scheme/port condition failures from true not-found results.
- `MatchResult::conditionNotMet()` named constructor and `isConditionNotMet()` convenience method.
- `MethodNotAllowedException::getAllowedMethods()` for typed array access to allowed HTTP methods.
- Matched `Route` object added as a request attribute (keyed by `Route::class`) during dispatch, enabling routing-aware middleware.
- Route freezing: routes become immutable after `prepareRoutes()`, preventing silent post-compilation misconfiguration.
- `FreezeableInterface`, `FreezeableTrait`, and `FreezeGuard` for route immutability lifecycle.
- Optional segment support in `generateUrl()`: routes using FastRoute `[/{param}]` syntax resolve correctly with defaults from `setVars()` or omit the segment when unset.

### Changed
- Minimum PHP version raised to 8.3.
- PHPStan analysis raised from level 4 to level 7 (#360).
- Switched from PHP_CodeSniffer (PSR-12) to PHP CS Fixer (PER-CS2.0).
- Switched from PHPUnit to Pest v4.
- Switched from PHPUnit mocks to Mockery.
- Test namespace changed from `League\Route\` to `League\Route\Test\`.
- Cached router completely redesigned: caches compiled FastRoute data (scalars only) instead of serialising the entire Router object (#353).
- `Route::setVars()` now sets declaration-time defaults only; dispatcher uses `setPathVars()` internally (#350).
- `Route::getVars()` returns merged result of default vars and path vars, with path vars taking precedence.
- Route compilation is now request-independent: all routes compiled unconditionally, condition matching at dispatch time only.
- `Cache\Router` now implements `RouterInterface`.
- `FileCache::getMultiple()`, `setMultiple()`, and `deleteMultiple()` now throw `BadMethodCallException` instead of returning incorrect values.
- `Dispatcher` now uses composition instead of inheriting from `FastRoute\Dispatcher\GroupCountBased` (#359).
- `Dispatcher` constructor accepts strategy and route map at construction time instead of via setters.
- `Dispatcher` no longer implements `RouteConditionHandlerInterface` (condition matching extracted as internal concern).
- `JsonStrategy::getOptionsCallable()` returned closure now accepts `(ServerRequestInterface $request, array $vars)` parameters, enabling CORS-aware OPTIONS handling (#361).
- `OptionsHandlerInterface::getOptionsCallable()` docblock now specifies the expected callable signature.
- Cache signature hash upgraded from md5 to xxh128 for improved collision resistance.
- `Dispatcher` switch statements replaced with `match` expressions for exhaustiveness checking.
- `MatchResult` is now a `readonly` class.
- `Route::setPathVars()` marked `@internal` (dispatcher-only operation, exempted from route freezing).

### Removed
- `laravel/serializable-closure` removed from hard dependencies (moved to suggest).
- Closure wrapping removed from `Route` constructor.
- BETA status removed from cached router.
- Request-dependent route filtering removed from `prepareRoutes()`.
- Support for PHP 8.1 and 8.2 dropped.
- Scrutinizer CI integration removed.
- `Dispatcher::setRouteMap()` removed (route map now set via constructor).
- Duplicate `Router::processGroups()` method removed (consolidated into `collectGroupRoutes()`).

## [6.2.0] 2024-11

### Changed
- Replaced opis/closure with laravel/serializable-closure and implemented throughout the handler process rather than a blanket serialisation of the router.

## [6.1.1] 2024-11

### Fixed
- Further fixes for type hinting bug related to array based callables with a string class name.

## [6.1.0] 2024-11

### Fixed
- Fixed a bug introduced in 6.0.0 where an array based callable with a string class name would not be considered valid.
- Added some doc comments for clarity on array types. (@marekskopal)

### Changed
- Updated `psr/http-message` to `^2.0.0`.

## [6.0.0] 2024-11

> Note: While this is a major release, there are no breaking changes to the public API. The major version bump is due to the removal of support for PHP 8.0 and below.
> 
> This being said, there are some internal changes that may affect you if you have extended the library in any way. Please test thoroughly before upgrading.

### Added
- Added full support for PHP 8.1 to 8.4.
- Ability to use a PSR-15 middleware as a controller.
- Ability to pass an array of HTTP methods to `Router::map` to create a route that matches multiple methods.
  - This method still accepts a string so is not a breaking change.
- Ability to add a custom key to a caching router.

### Changed
- Fixes and improvements throughout for PHP 8.1 to 8.4.

### Removed
- Removed support for PHP < 8.1.

## [5.1.0] 2021-07

### Added
- Support for named routes within groups (@Fredrik82)

## [5.0.1] 2021-03

### Added
- Support for `psr/container:2.0`

## [5.0.0] 2021-01

### Added
- A cached router, a way to have a fully built router cached and resolved from cache on subsequent requests.
- Response decorators, a way to manipulate a response object returned from a matched route.
- Automatic generation of OPTIONS routes if they have not been defined.

### Changed
- Minimum PHP requirement bumped to 7.2.
- `Router` no longer extends FastRoute `RouteCollecter`.
    - `Router` constructor no longer accepts optional FastRoute `RouteParser` and `DataGenerator`.
    - `Router` constructor now accepts an optional FastRoute `RouteCollector`.
        - Routes already registered with FastRoute `RouteCollector` are respected and matched.
- Separated route preparation from dispatch process so that the router can dispatch multiple times.
- General code improvements.

### Removed
- Setting of default response headers on strategies. (Replaced by response decorators, see Added).
- Exception handlers from strategies. (Already deprecated in favour of throwable handlers).

## [4.5.1] 2021.01

### Added
- Official support for PHP 8.0.

## [4.5.0] 2020-05

### Added
- Ability to pass optional `$replacements` array to `Route::getPath` in order to build literal route path strings.

## [4.4.0] 2020-05

### Added
- Ability to pass JSON flags to JsonStrategy. (@pine3ree)
- Router is now a RequestHandlerInterface so can be used as a middleware itself. (@delboy1978uk)
- Route params now added as Request attributes. (@delboy1978uk)

### Fixed
- Exception moved to more appropriate place when shifting no middleware. (@delboy1978uk)
- Ensure group prefix is always added when adding a parent group. (@delboy1978uk)


## [4.3.1] 2019-07

### Fixed
- Fixed bug when attempting to get a container for custom strategy that is not container aware.

## [4.3.0] 2019-06

### Added
- Ability to add middleware to the stack as a class name so it is only instantiated when used.

### Changed
- Switch to use `zendframework/zend-httphandlerrunner` as removed from `diactoros` (@JohnstonCode)

### Fixed
- When adding a prefix to a group after adding routes, it is now applied to those routes. (@delboy1978uk)
- Fix to how shifting middleware is handled to prevent error triggering. (@delboy1978uk)
- Fix to ensure that when invoking FastRoute methods on League\Route all callables are converted to League\Route objects (@pgk)
- Various documentation fixes.

## [4.2.0] 2018-10

### Added
- Allow adding default response headers to strategies.
- Expand error handling to include Throwable.

## [4.1.1] 2018-10

### Fixed
- Fixed issue where group middleware was being dublicated on internal routes.

## [4.1.0] 2018-09

### Changed
- JSON strategy now allows array and object returns and builds JSON response. (Thanks @willemwollebrants)

### Fixed
- Fixed issue where setting strategy on specific routes had no effect. (Thanks @aag)

## [4.0.1] 2018-08

### Fixed
- Fixed a bug where content-type header would not be added to response in Json Strategy.

## [4.0.0] 2018-08

### Changed
- Increased minimum PHP version to 7.1.0
- Now implements PSR-15 middleware and request handlers.
- No longer enforces use of container, one can be used optionally.
- Strategies now return PSR-15 middleare as handlers.
- Increased types of proxy callables that can be used as controllers.
- General housekeeping and refactoring for continued improvement.

### Fixed
- Group level strategies now handle exceptions if a route is not matched but the request falls within the group.

## [3.1.0] 2018-07

### Fixed
- Ensure JsonStrategy handles all exceptions by default.
- Handle multiline exception messages.

### Added
- Add port condition to routes.

## 3.0.4 2017-03

### Fixed
- Middleware execution order.

## 3.0.0 2017-03

## Added
- Middleware functionality for PSR-7 compatible callables, globally to route collection or individually per route/group.
- Allow setting of strategy for a route group.
- Add UUID as default pattern matcher.

## Changed
- Now depend directly on PSR-11 implementation.
- Simplified default strategies to just `Application` and `Json`.
- Have strategies return a middleware to add to the stack.
- Have strategies handle decoration of exceptions.

## 2.0.2 - 2018-07

### Fixed
- Have JsonStrategy handle all exceptions by default.

## 2.0.0 - 2016-02

### Added
- All routing and dispatching now built around PSR-7.
- Can now group routes with prefix and match conditions.
- Routes now stored against a specific `Route` object that describes the route.
- New `dispatch` method on `RouteCollection` that is a compliant PSR-7 middleware.
- Additional route matching conditions for scheme and host.

### Changed
- API rewrite to simplify.
- API naming improvements.
- Strategies now less opinionated about return from controller.

## [1.2.0] - 2015-08

### Added
- Can now use any callable as a controller.
- Request object is now built by the strategy when one is not available from the container.

### Fixed
- General tidying and removal of unused code.
- URI variables now correctly passed to controller in `MethodArgumentStrategy`.

## [1.1.0] - 2015-02

### Added
- Added `addPatternMatcher` method to allow custom regex shortcuts within wildcard routes.
- Refactored logic around matching routes.

## [1.0.1] - 2015-01

### Fixed
- Added import statements for all used objects.
- Fixed dockblock annotations.
- PSR-2 standards improvements within tests.

## 1.0.0 - 2015-01

### Added
- Migrated from [Orno\Route](https://github.com/orno/route).
