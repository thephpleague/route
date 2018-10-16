---
layout: post
title: HTTP
sections:
    Introduction: introduction
    The Request: the-request
    The Response: the-response
---
## Introduction

HTTP messages form the core of any modern web application. Route is built with this in mind, so it dispatches a [PSR-7](https://www.php-fig.org/psr/psr-7/) request object and expects a [PSR-7](https://www.php-fig.org/psr/psr-7/) response object to be returned by your controller or middleware.

We also make use of [PSR-15](https://www.php-fig.org/psr/psr-15/) request handlers and middleware.

Throughout this documentation, we will be using [zend-diactoros](https://zendframework.github.io/zend-diactoros/) to provide our HTTP messages but any implementation is supported.

## The Request

Route dispatches a `Psr\Http\Message\ServerRequestInterface` implementations, passes it through your middleware and finally to your controller as the first argument of the controller callable.

### Middleware Signature

Middlewares should be implementations of `Psr\Http\Server\MiddlewareInterface`, the request implementation will be passed as the first argument and an implementation of `Psr\Http\Server\RequestHandlerInterface` will be passed as the second argument so that you can trigger the next middleware in the stack.

An example middleware would look something like this.

~~~php
<?php declare(strict_types=1);

namespace Acme\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SomeMiddleware implements MiddlewareInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        // ...
        return $handler->handler($request);
    }
}
~~~

Read more about middleware [here](/4.x/middleware).

### Controller Signature

A basic controller signature should look something like this.

~~~php
<?php declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface;

function controller(ServerRequestInterface $request) {
    // ...
}
~~~

See more about controllers [here](/4.x/controllers).

### Request Input

Route does not provide any functionality for dealing with globals such as `$_GET`, `$_POST` etc, this is all handled by your [PSR-7](https://www.php-fig.org/psr/psr-7/) implementation, please refer to that documentation for details on how to interact with input on the request object.

## The Response

Because Route is built around PSR-15, this means that middleware and controllers are handles in a [single pass](https://www.php-fig.org/psr/psr-15/meta/#52-single-pass-lambda) approach. What this means in practice is that all middleware is passed a request object but is expected to build and return its own response or pass off to the next middleware in the stack for that to create one. Any controller that is dispatched via Route is wrapped in a middleware that adheres to this.

Once wrapped, your controller ultimately becomes the last middleware in the stack (this does not mean that it has to be invoked last, see [middleware](/4.x/middleware) for more on this), it just means that it will only be concerned with creating and returning a response object.

An example of a controller building a response might look like this.

~~~php
<?php declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;

function controller(ServerRequestInterface $request) : ResponseInterface {
    $response = new Response;
    $response->getBody()->write('<h1>Hello, World!</h1>');
    return $response;
}
~~~

Route does not provide any functionality for creating or interacting with a response object. For more information, please refer to the documentation of the [PSR-7](https://www.php-fig.org/psr/psr-7/) implementation that you have chosen to use
