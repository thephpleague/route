# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

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
