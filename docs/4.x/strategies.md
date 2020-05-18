---
layout: post
title: Strategies
sections:
    Introduction: introduction
    Applying Strategies: applying-strategies
    Application Strategy: application-strategy
    JSON Strategy: json-strategy
    Default Response Behaviour: default-response-behaviour
    Custom Strategies: custom-strategies
---
## Introduction

Strategies are a way of defining how a route callable is dispatched. A strategy defines what to do if a route is matched, if no route is found and what to do in certain error conditions.

Route provides two strategies out of the box, one aimed at standard web apps and one aimed at JSON APIs.

- `League\Route\Strategy\ApplicationStrategy` (Default)
- `League\Route\Strategy\JsonStrategy` (Requires a HTTP Response Factory)

## Applying Strategies

Strategies can be applied in three ways, each takes precedence over the previous.

### Globally

Will apply to all routes defined by the router unless the route or its parent group has a different strategy applied.

~~~php
<?php declare(strict_types=1);

use League\Route\Strategy\ApplicationStrategy;

$router = new League\Route\Router;
$router->setStrategy(new ApplicationStrategy);
~~~

### Per Group

Applying a strategy to a group will apply it to all routes defined within that group as well as any errors that occur when a request is within the group prefix. In these cases, any globally applied strategy will be ignored.

~~~php
<?php declare(strict_types=1);

use League\Route\Strategy\ApplicationStrategy;

$router = new League\Route\Router;

$router
    ->group('/group', function ($router) {
        $router->map('GET', '/acme/route', 'Acme\Controller::action');
        $router->put('/acme/route', 'Acme\Controller::action');
    })
    ->setStrategy(new ApplicationStrategy)
;
~~~

### Per Route

A strategy can be applied to any specific route, at top level or within a group, this will take precedence over any strategy applied to its parent group or globally.

~~~php
<?php declare(strict_types=1);

use Acme\CustomStrategy;
use League\Route\Strategy\ApplicationStrategy;

$router = new League\Route\Router;

$router->map('GET', '/acme/route', 'Acme\Controller::action')->setStrategy(new CustomStrategy);

$router
    ->group('/group', function ($router) {
        $router
            ->map('GET', '/acme/route', 'Acme\Controller::action')
            ->setStrategy(new CustomStrategy) // will ignore the strategy applied to the group
        ;
    })
    ->setStrategy(new ApplicationStrategy)
;
~~~

## Application Strategy

`League\Route\Strategy\ApplicationStrategy` is used by default, it provides the controller with a PSR-7 `Psr\Http\Message\ServerRequestInterface` implementation and any route arguments. It expects your controller to build and return an implementation of `Psr\Http\Message\ResponseInterface`.

### Controller Signature

~~~php
<?php declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;

function controller(ServerRequestInterface $request, array $args) : ResponseInterface {
    // ...
    $response = new Response;
    $response->getBody()->write(/* $content */);
    return $response->withStatus(200);
});
~~~

### Exception (Throwable) Decorators

The application strategy simply allows any `Throwable` to bubble out, you can catch them in your bootstrap process or you have the option to extend this strategy and overload the exception/throwable decorator methods. See [Custom Strategies](#custom-strategies).

*Note:* In version `5.x` exception decorators will be replaced completely with throwable decorators, keep this in mind when implementing custom strategies, recommendation is to proxy your exception decorator to the throwable decorator.

## JSON Strategy

`League\Route\Strategy\JsonStrategy` aims to make building JSON APIs a little easier. It provides a PSR-7 `Psr\Http\Message\ServerRequestInterface` implementation and any route arguments to the controller as with the application strategy, the difference being that you can either build and return a response yourself or return an array or object, and a JSON response will be built for you.

To make use of the JSON strategy, you will need to provide it with a [PSR-17](https://www.php-fig.org/psr/psr-17/) response factory implementation. Some examples of HTTP Factory packages can be found [here](https://github.com/http-interop?utf8=%E2%9C%93&q=http-factory&type=&language=). We will use the `zend-diactoros` factory as an example.

~~~php
<?php declare(strict_types=1);

$responseFactory = new Http\Factory\Diactoros\ResponseFactory;
$strategy = new League\Route\Strategy\JsonStrategy($responseFactory);

$router = (new League\Route\Router)->setStrategy($strategy);
~~~

### Controller Signature

~~~php
<?php declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;

function responseController(ServerRequestInterface $request, array $args) : ResponseInterface {
    // ...
    $response = new Response;
    $response->getBody()->write(json_encode(/* $content */));
    return $response->withAddedHeader('content-type', 'application/json')->withStatus(200);
});

function arrayController(ServerRequestInterface $request, array $args) : array {
    // ...
    return [
        // ...
    ];
});
~~~

### JSON Flags

You can pass an optional second argument to the `JsonStrategy` to define the JSON flags to use when encoding the response.

~~~php
<?php declare(strict_types=1);

$responseFactory = new Http\Factory\Diactoros\ResponseFactory;
$strategy = new League\Route\Strategy\JsonStrategy($responseFactory, JSON_BIGINT_AS_STRING);

$router = (new League\Route\Router)->setStrategy($strategy);
~~~


### Exception Decorators

`League\Route\Strategy\JsonStrategy` will decorate all exceptions, `NotFound`, `MethodNotAllowed`, and any 4xx or 5xx exceptions as a JSON Response, setting the correct HTTP status code and content type header in the process.

~~~json
{
    "status_code": 404,
    "message": "Not Found"
}
~~~

#### HTTP 4xx Exceptions

In a RESTful API, covering all outcomes and returning the correct 4xx response can become quite verbose. Therefore, the dispatcher provides a convenient way to ensure you can return the correct response without the need for a conditional being created for every outcome.

Simply throw one of the HTTP exceptions from within your application layer and the strategy will catch the exception and build the appropriate response.

~~~php
<?php declare(strict_types=1);

use League\Route\Http\Exception\BadRequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$router = new League\Route\Router;

$router->post('/acme', function (ServerRequestInterface $request) : ResponseInterface {
    throw new BadRequestException;
});
~~~

##### Available HTTP Exceptions

| Status Code | Exception                                                         | Description                                                                                                                                                                                                  |
| ----------- | ----------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 400         | `League\Route\Http\Exception\BadRequestException`                 | The request cannot be fulfilled due to bad syntax.                                                                                                                                                           |
| 401         | `League\Route\Http\Exception\UnauthorizedException`               | Similar to 403 Forbidden, but specifically for use when authentication is required and has failed or has not yet been provided.                                                                              |
| 403         | `League\Route\Http\Exception\ForbiddenException`                  | The request was a valid request, but the server is refusing to respond to it.                                                                                                                                |
| 404         | `League\Route\Http\Exception\NotFoundException`                   | The requested resource could not be found but may be available again in the future.                                                                                                                          |
| 405         | `League\Route\Http\Exception\MethodNotAllowedException`           | A request was made of a resource using a request method not supported by that resource; for example, using GET on a form which requires data to be presented via POST, or using PUT on a read-only resource. |
| 406         | `League\Route\Http\Exception\NotAcceptableException`              | The requested resource is only capable of generating content not acceptable according to the Accept headers sent in the request.                                                                             |
| 409         | `League\Route\Http\Exception\ConflictException`                   | Indicates that the request could not be processed because of conflict in the request, such as an edit conflict in the case of multiple updates.                                                              |
| 410         | `League\Route\Http\Exception\GoneException`                       | Indicates that the resource requested is no longer available and will not be available again.                                                                                                                |
| 411         | `League\Route\Http\Exception\LengthRequiredException`             | The request did not specify the length of its content, which is required by the requested resource.                                                                                                          |
| 412         | `League\Route\Http\Exception\PreconditionFailedException`         | The server does not meet one of the preconditions that the requester put on the request.                                                                                                                     |
| 415         | `League\Route\Http\Exception\UnsupportedMediaException`           | The request entity has a media type which the server or resource does not support.                                                                                                                           |
| 417         | `League\Route\Http\Exception\ExpectationFailedException`          | The server cannot meet the requirements of the Expect request-header field.                                                                                                                                  |
| 418         | `League\Route\Http\Exception\ImATeapotException`                  | [I'm a teapot](http://en.wikipedia.org/wiki/April_Fools%27_Day_RFC).                                                                                                                                         |
| 428         | `League\Route\Http\Exception\PreconditionRequiredException`       | The origin server requires the request to be conditional.                                                                                                                                                    |
| 429         | `League\Route\Http\Exception\TooManyRequestsException`            | The user has sent too many requests in a given amount of time.                                                                                                                                               |
| 451         | `League\Route\Http\Exception\UnavailableForLegalReasonsException` | The resource is unavailable for legal reasons.                                                                                                                                                               |

## Default Response Behaviour

You can define default interactions/behavior for the response before it is sent.

### Headers

You can set a header to be applied to the response on every request, it will only be applied, if the header does not already exist. For example, you may want to set a custom `Content-Type` header.

~~~php
<?php declare(strict_types=1);

$responseFactory = new Http\Factory\Diactoros\ResponseFactory;
$strategy = new League\Route\Strategy\JsonStrategy($responseFactory);

$strategy->setDefaultResponseHeader('content-type', 'acme-app/json');

$router = (new League\Route\Router)->setStrategy($strategy);
~~~

## Custom Strategies

You can build your own custom strategy to use in your application as long as it is an implementation of `League\Route\Strategy\StrategyInterface`. A strategy is tasked with:

1. Providing a middleware that invokes your controller then decorates and returns your controllers response.
2. Providing a middleware that will decorate a 404 `NotFoundException` and return a response.
3. Providing a middleware that will decorate a 405 `MethodNotAllowedException` and return a response.
4. Providing a middleware that will decorate any other exception and return a response.

~~~php
<?php

namespace League\Route\Strategy;

use Exception;
use League\Route\Http\Exception\{MethodNotAllowedException, NotFoundException};
use League\Route\Route;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\MiddlewareInterface;

interface StrategyInterface
{
    /**
     * Invoke the route callable based on the strategy.
     *
     * @param \League\Route\Route                      $route
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function invokeRouteCallable(Route $route, ServerRequestInterface $request) : ResponseInterface;

    /**
     * Get a middleware that will decorate a NotFoundException
     *
     * @param \League\Route\Http\Exception\NotFoundException $exception
     *
     * @return \Psr\Http\Server\MiddlewareInterface
     */
    public function getNotFoundDecorator(NotFoundException $exception) : MiddlewareInterface;

    /**
     * Get a middleware that will decorate a NotAllowedException
     *
     * @param \League\Route\Http\Exception\NotFoundException $exception
     *
     * @return \Psr\Http\Server\MiddlewareInterface
     */
    public function getMethodNotAllowedDecorator(MethodNotAllowedException $exception) : MiddlewareInterface;

    /**
     * Get a middleware that acts as an exception handler, it should wrap the rest of the
     * middleware stack and catch eny exceptions.
     *
     * @return \Psr\Http\Server\MiddlewareInterface
     */
    public function getExceptionHandler() : MiddlewareInterface;
}
~~~

The best way to learn how to create a custom strategy is to look at the strategies that Route provides by default, they can be found [here](https://github.com/thephpleague/route/tree/master/src/Strategy).
