---
layout: post
title: Strategies
sections:
    Introduction: introduction
    Request Response Strategy: request-response-strategy
    Parameter Strategy: parameter-strategy
    JSON Strategy: json-strategy
    Custom Strategies: custom-strategies
---
## Introduction

Route strategies are a way of defining specific behaviour and controller signatures for a route.

- `League\Route\Strategy\RequestResponseStrategy`
- `League\Route\Strategy\ParamStrategy`
- `League\Route\Strategy\JsonStrategy`

Strategies can be set individually per route by setting it on the route.

~~~php
<?php

use League\Route\Strategy\RequestResponseStrategy;
use League\Route\Strategy\ParamStrategy;
use League\Route\Strategy\JsonStrategy;

$route = new League\Route\RouteCollection;

$route->map('GET', '/acme/route', 'Acme\Controller::action')->setStrategy(new RequestResponseStrategy);
$route->get('/acme/route', 'Acme\Controller::action')->setStrategy(new ParamStrategy);
$route->put('/acme/route', 'Acme\Controller::action')->setStrategy(new JsonStrategy);
~~~

Or a global strategy can be set to be used by all routes in a specific collection.

~~~php
use League\Route\Strategy\JsonStrategy;

$route = new League\Route\RouteCollection;

$route->setStrategy(new JsonStrategy);
~~~

## Request Response Strategy

The `RequestResponseStrategy` is used by default and provides the controller with a PSR-7 `ServerRequestInterface` implementation and `ResponseInterface` implementation. The idea here being that you can pull any information you need from the request, manipulate the response, and return it for the dispatcher to send to the browser.

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
    $userId = $args['id'];
    $requestBody = json_decode($request->getBody(), true);

    // possibly update a record in the database with the request body

    $response->getBody()->write('Updated User with ID: ' . $userId);

    return $response->withStatus(202);
});
~~~

Whilst these are primitive and naive examples, it is good design to handle your request and response lifecycle in this way as you are fully in control of input and output.

## Parameter Strategy

The parameter strategy uses reflection via [league/container](http://container.thephpleague.com) to invoke your controller and pass in any route parameters along with any type hinted dependencies on the method.

~~~php
<?php

use Psr\Http\Message\ServerRequestInterface;

$route = new League\Route\RouteCollection;

$route->setStrategy(new League\Route\Strategy\ParamStrategy);

$route->get('/hello/{name1}/{name2}', function (ServerRequestInterface $request, $name1, $name2) {
    return '<h1>Hello ' . $name1 . ' and ' . $name2 . '</h1>';
});
~~~

When the above controller is invoked, the strategy will reflect on its parameters, attempt to resolve `ServerRequestInterface` from the container, and pass the dynamic parts of the route to the corresponding parameter names.

## JSON Strategy

The JSON strategy aims to make building RESTful APIs a little easier. It is passed a PSR-7 `ServerRequestInterface` implementation and any route arguments and expects the controller to return either a PSR-7 `ResponseInterface` implementation or data in a format that can be converted to JSON.

~~~php
<?php

use Psr\Http\Message\ServerRequestInterface;

$route = new League\Route\RouteCollection;

// this route would be considered a "get all" resource
$router->get('/acme', function (ServerRequestInterface $request) {
    // pull data from $request and do something clever

    return [
        // ... data to be converted to json
    ];
});

// this route would be considered a "get one" resource
$router->get('/acme/{id}', function (ServerRequestInterface $request, array $args) {
    // get any required data from $request and find entity relating to $args['id']

    return [
        // ... data to be converted to json
    ];
});
~~~

The problem with returning an array is that you are always assuming a `200 OK` HTTP response code, most PSR-7 implementations are likely to provide some convenience `ResponseInterface` implementations that will allow you to easily build a JSON response.

### HTTP 4xx Exceptions

In a RESTful API, covering all outcomes and returning the correct 4xx response can become quite verbose. Therefore, the dispatcher provides a convenient way to ensure you can return the correct response without the need for a conditional being created for every outcome.

Simply throw one of the HTTP exceptions from within your application layer and the dispatcher will catch the exception and build the appropriate response.

~~~php
<?php

use League\Route\Http\Exception\BadRequestException;
use Psr\Http\Message\ServerRequestInterface;

$router->post('/acme', function (ServerRequestInterface $request) {
    // create a record from the $request body

    // if we fail to insert due to a bad request
    throw new BadRequestException;

    // ...
});
~~~

If the exception is thrown, a request with the correct response code and headers is built containing the following body.

~~~json
{
    "status_code": 400,
    "message": "Bad Request"
}
~~~

#### Available HTTP Exceptions

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
| 451         | `League\Route\Http\Exception\UnavailableForLegalReasonsException` | The resource is unavailable for legal reasons.                                                                                                                                                              |

## Custom Strategies

Route allows you to define a custom dispatch strategy by implementing `League\Route\Strategy\StrategyInterface`.

~~~php
<?php

namespace Acme\Strategy;

use League\Route\Strategy\StrategyInterface;

class CustomStrategy implements StrategyInterface
{
    public function dispatch($controller, array $vars)
    {
        // ... handle the dispatch of the controller yourself
    }
}
~~~

~~~php
use Acme\Strategy\CustomStrategy;

$route = new League\Route\RouteCollection;

$route->setStrategy(new CustomStrategy);
~~~

Now when the route is dispatched, the `dispatch` method of the custom strategy will be invoked and passed arguments needed to invoke a controller.

The `$controller` argument will be one of three types, `string` (points to a named function), `array` (points to a class method `[0 => 'ClassName', 1 => 'methodName']`) or `\Closure` (is an anonymous function), and the `$vars` argument is an associative array of wildcard segments from the matched route `['wildcard' => 'actual_value']`.

The return of your dispatch method will bubble out and be returned by `League\Route\Dispatcher::dispatch`, it does not require a return value, however, you should be aware that there is no output buffering within the dispatch process by default.
