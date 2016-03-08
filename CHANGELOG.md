# Changelog

All Notable changes to `League\Route` will be documented in this file

## 2.0.0 - 2016-02

### Added
- All routing and dispatching now built around PSR-7.
- Can now group routes with prefix and match conditions.
- Routes now stored against a specific `Route` object that describes the route.
- New `dispatch` method on `RouteCollection` that is a compliant PSR-7 middleware.
- Additional route matching conditions for scheme and host.

## Changed
- API rewrite to simplify.
- API naming improvements.
- Strategies now less opinionated about return from controller.

## 1.2.0 - 2015-08-24

### Added
- Can now use any callable as a controller.
- Request object is now built by the strategy when one is not available from the container.

### Fixed
- General tidying and removal of unused code.
- URI variables now correctly passed to controller in `MethodArgumentStrategy`.

## 1.1.0 - 2015-02-24

### Added
- Added `addPatternMatcher` method to allow custom regex shortcuts within wildcard routes.
- Refactored logic around matching routes.

## 1.0.1 - 2015-01-29

### Fixed
- Added import statements for all used objects.
- Fixed dockblock annotations.
- PSR-2 standards improvements within tests.

## 1.0.0 - 2015-01-09

### Added
- Migrated from [Orno\Route](https://github.com/orno/route).
