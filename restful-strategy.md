---
layout: default
permalink: /restful-strategy/
title: RestfulStrategy
---

# RestfulStrategy

The `RestfulStrategy` is aimed at making life a little but easier when building RESTful APIs. When using this strategy a `Request` object will be passed in to your callable along with an optional array of named wildcard route values.

It is expected that a `Response` object or data of a type that can be converted to JSON is returned.

~~~ php
use Symfony\Component\HttpFoundation\Request;

// this route would be considered a "get all" resource
$route->get('/acme', function (Request $request) {
    // pull data from $request and do something clever

    return [
        // ... data to be converted to json
    ];
});

// this route would be considered a "get one" resource
$route->get('/acme/{id}', function (Request $request, array $args) {
    // get any required data from $request and find enitity relating to $args['id']

    return [
        // ... data to be converted to json
    ];
});
~~~

The problem with returning an array is that you are always assuming a `200 OK` HTTP response code.

## Pre-built JSON Responses

Route provides several pre-built JSON `Response` objects that are pre-configured and will handle the response for you.

For example, when creating a resource, on sucess we would likely return a `201 Created` response. This can be done very easily.

~~~ php
use League\Route\Http\JsonResponse as Response;

$route->post('/acme', function (Request $request) {
    // create a record from the $request body

    return new Response\Created([
        // ... data to be converted to json
    ]);
});
~~~

The above route will return a response with the correct `201` status code and a body JSON converted from the array passed in to the response.

### Available JSON Responses

| Response Object                                                          | Status Code | Notes                   |
| ------------------------------------------------------------------------ | ----------- | ----------------------- |
| `League\Route\Http\JsonResponse\Ok`                                      | 200         |                         |
| `League\Route\Http\JsonResponse\Created`                                 | 201         |                         |
| `League\Route\Http\JsonResponse\Accepted`                                | 202         |                         |
| `League\Route\Http\JsonResponse\NoContent`                               | 204         | Will not return a body. |
| `League\Route\Http\JsonResponse\ResetContent`                            | 205         |                         |
| `League\Route\Http\JsonResponse\PartialContent`                          | 206         |                         |

## HTTP 4xx Exceptions

In a RESTful API, covering all outcomes and returning the correct 4xx response can become quite verbose. Therefore, the dispatcher provides a convenient way to ensure you can return the correct response without the need for a conditional being created for every outcome.

Simply throw one of the HTTP exceptions from within your application layer and the dispatcher will catch the exception and build the appropriate response.

~~~ php
use League\Route\Http\Exception\BadRequestException;

$route->post('/acme', function (Request $request) {
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

### Available HTTP Exceptions

| Status Code | Exception                                                   | Description                                                                                                                                                                                                  |
| ----------- | ----------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 400         | `League\Route\Http\Exception\BadRequestException`           | The request cannot be fulfilled due to bad syntax.                                                                                                                                                           |
| 401         | `League\Route\Http\Exception\UnauthorizedException`         | Similar to 403 Forbidden, but specifically for use when authentication is required and has failed or has not yet been provided.                                                                              |
| 403         | `League\Route\Http\Exception\ForbiddenException`            | The request was a valid request, but the server is refusing to respond to it.                                                                                                                                |
| 404         | `League\Route\Http\Exception\NotFoundException`             | The requested resource could not be found but may be available again in the future.                                                                                                                          |
| 405         | `League\Route\Http\Exception\MethodNotAllowedException`     | A request was made of a resource using a request method not supported by that resource; for example, using GET on a form which requires data to be presented via POST, or using PUT on a read-only resource. |
| 406         | `League\Route\Http\Exception\NotAcceptableException`        | The requested resource is only capable of generating content not acceptable according to the Accept headers sent in the request.                                                                             |
| 409         | `League\Route\Http\Exception\ConflictException`             | Indicates that the request could not be processed because of conflict in the request, such as an edit conflict in the case of multiple updates.                                                              |
| 410         | `League\Route\Http\Exception\GoneException`                 | Indicates that the resource requested is no longer available and will not be available again.                                                                                                                |
| 411         | `League\Route\Http\Exception\LengthRequiredException`       | The request did not specify the length of its content, which is required by the requested resource.                                                                                                          |
| 412         | `League\Route\Http\Exception\PreconditionFailedException`   | The server does not meet one of the preconditions that the requester put on the request.                                                                                                                     |
| 415         | `League\Route\Http\Exception\UnsupportedMediaException`     | The request entity has a media type which the server or resource does not support.                                                                                                                           |
| 417         | `League\Route\Http\Exception\ExpectationFailedException`    | The server cannot meet the requirements of the Expect request-header field.                                                                                                                                  |
| 418         | `League\Route\Http\Exception\ImATeapotException`            | [I'm a teapot](http://en.wikipedia.org/wiki/April_Fools%27_Day_RFC).                                                                                                                                         |
| 428         | `League\Route\Http\Exception\PreconditionRequiredException` | The origin server requires the request to be conditional.                                                                                                                                                    |
| 429         | `League\Route\Http\Exception\TooManyRequestsException`      | The user has sent too many requests in a given amount of time.                                                                                                                                               |

