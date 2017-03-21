---
layout: default
permalink: /application-strategy/
title: ApplicationStrategy
---

# ApplicationStrategy

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

## Exception Decorators

The `ApplicationStrategy` simply allows any exceptions to bubble out, you can catch them in your bootstrap process or you have the option to extend this strategy and overload the exception decorator methods. See [Custom Strategies](/custom-strategies).
