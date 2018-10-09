---
layout: post
title: Strategies
sections:
    Introduction: introduction
    Application Strategy: application-strategy
    JSON Strategy: json-strategy
    Custom Strategies: custom-strategies
---
## Introduction

Route strategies are a way of defining specific behaviour and controller signatures for a route.

- `League\Route\Strategy\ApplicationStrategy`
- `League\Route\Strategy\JsonStrategy`

Strategies can be set individually per route by setting it on the route.

~~~php
<?php

use League\Route\Strategy\ApplicationStrategy;
use League\Route\Strategy\JsonStrategy;

$route = new League\Route\RouteCollection;

$route->map('GET', '/acme/route', 'Acme\Controller::action')->setStrategy(new ApplicationStrategy);
$route->put('/acme/route', 'Acme\Controller::action')->setStrategy(new JsonStrategy);
~~~

By group so that the strategy will be used on any route in that group.

~~~php
<?php

use League\Route\Strategy\ApplicationStrategy;
use League\Route\Strategy\JsonStrategy;

$route = new League\Route\RouteCollection;

$route->group('/group', function ($route) {
    $route->map('GET', '/acme/route', 'Acme\Controller::action');
    $route->put('/acme/route', 'Acme\Controller::action');
})->setStrategy(new ApplicationStrategy);
~~~

Or a global strategy can be set to be used by all routes in a specific collection.

~~~php
use League\Route\Strategy\JsonStrategy;

$route = new League\Route\RouteCollection;

$route->setStrategy(new JsonStrategy);
~~~

## Application Strategy

The `ApplicationStrategy` is used by default and provides the controller with a PSR-7 `ServerRequestInterface` implementation, a `ResponseInterface` implementation and any route arguments. The idea here being that you can pull any information you need from the request, manipulate the response, and return it for the dispatcher to send to the browser. This strategy expects that your controller returns a `ResponseInterface` implementation.

~~~php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$router->get('/acme/route', function (ServerRequestInterface $request, ResponseInterface $response) {
    // retrieve data from $request, do what you need to do and build your $content

    $response->getBody()->write($content);

    return $response->withStatus(200);
});
~~~

~~~php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$router->put('/user/{id}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) {
    $userId      = $args['id'];
    $requestBody = json_decode($request->getBody(), true);

    // possibly update a record in the database using the request body

    $response->getBody()->write('Updated User with ID: ' . $userId);

    return $response->withStatus(202);
});
~~~

Whilst these are primitive and naive examples, it is good design to handle your request and response lifecycle in this way as you are fully in control of input and output.

### Exception Decorators

The `ApplicationStrategy` simply allows any exceptions to bubble out, you can catch them in your bootstrap process or you have the option to extend this strategy and overload the exception decorator methods. See [Custom Strategies](#custom-strategies).

## Json Strategy

The `JsonStrategy` aims to make building REST APIs a little easier. It provides a PSR-7 `ServerRequestInterface` implementation and `ResponseInterface` implementation and any route arguments. This strategy expects that your controller returns a `ResponseInterface` implementation. Most PSR-7 implementations are likely to provide some convenience `ResponseInterface` implementations that will allow you to easily build a JSON response.

~~~php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$router->get('/acme/route', function (ServerRequestInterface $request, ResponseInterface $response) {
    // retrieve data from $request, do what you need to do and build your $content

    $response->getBody()->write(json_encode($content));

    return $response->withStatus(200);
});
~~~

~~~php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$router->put('/user/{id}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) {
    $userId      = $args['id'];
    $requestBody = json_decode($request->getBody(), true);

    // possibly update a record in the database using the request body and get an array of the $user

    $response->getBody()->write(json_encode($user));

    return $response->withStatus(202);
});
~~~

### Exception Decorators

The `JsonStrategy` will decorate all exceptions, `NotFound`, `MethodNotAllowed`, and any 4xx or 5xx exceptions as a JSON Response, setting the correct HTTP status code in the process.

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
<?php

use League\Route\Http\Exception\BadRequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$router->post('/acme', function (ServerRequestInterface $request, ResponseInterface $response) {
    // create a record from the $request body

    // if we fail to insert due to a bad request
    throw new BadRequestException;

    // ...
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

## Custom Strategies

You can build your own custom strategy to use in your application as long as it is an implementation of `League\Route\Strategy\StrategyInterface`. A strategy is tasked with:

1. Providing a callable that decorates and returns your controllers response.
2. Providing a callable that will decorate a 404 Not Found Exception and return a response.
3. Providing a callable that will decorate a 405 Method Not Allowed Exception.
4. Providing a callable that will decorate any other exception.

Each of these callables should implement a PSR-7 compatible middleware signature.

~~~php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerResponseInterface;

function (ServerRequestInterface $request, ResponseInterface $response, callable $next) {
    // ...
    return $response;

    // or
    return $next($request, $response);
}
~~~

This is so that the callable can be appended to the current middleware stack and executed as the request completes.

~~~php
<?php

namespace League\Route\Strategy;

use \Exception;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Route;

interface StrategyInterface
{
    /**
     * Return a PSR-7 compatible middleware callable.
     *
     * ```
     * return function ($request, $response, $next) {
     *     // ...
     *     return $next($request, $response);
     * }
     * ```
     *
     * @param \League\Route\Route $route
     * @param array               $vars - named route arguments
     *
     * @return callable
     */
    public function getCallable(Route $route, array $vars);

    /**
     * Tasked with handling a not found exception, expects a
     * PSR-7 compatible middleware/callable to be returned
     * or for the exception to simply be thrown.
     *
     * ```
     * throw $exception;
     * ```
     * or
     * ```
     * return function ($request, $response) {
     *     // ...
     *     // it is recommended to return the response when decorating an exception
     *     return $response;
     * }
     * ```
     *
     * @param \League\Route\Http\Exception\NotFoundException $exception
     *
     * @return callable
     */
    public function getNotFoundDecorator(NotFoundException $exception);

    /**
     * Taskied with handling a not allowed exception, expects a
     * PSR-7 compatible middleware/callable to be returned
     * or for the exception to simply be thrown.
     *
     * ```
     * throw $exception;
     * ```
     * or
     * ```
     * return function ($request, $response) {
     *     // ...
     *     // it is recommended to return the response when decorating an exception
     *     return $response;
     * }
     * ```
     *
     * @param \League\Route\Http\Exception\MethodNotAllowedException $exception
     *
     * @return callable
     */
    public function getMethodNotAllowedDecorator(MethodNotAllowedException $exception);

    /**
     * Taskied with handling a standard exception, expects a
     * PSR-7 compatible middleware/callable to be returned
     * or for the exception to simply be thrown.
     *
     * ```
     * throw $exception;
     * ```
     * or
     * ```
     * return function ($request, $response) {
     *     // ...
     *     // it is recommended to return the response when decorating an exception
     *     return $response;
     * }
     * ```
     *
     * @param \Exception $exception
     *
     * @return callable
     */
    public function getExceptionDecorator(Exception $exception);
}
~~~

When working in production with the `ApplicationStrategy` it is recommended that you extend that strategy and overload the exception decorator methods to gracefully handle errors.
