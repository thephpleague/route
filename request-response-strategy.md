---
layout: default
permalink: /request-response-strategy/
title: RequestResponseStrategy
---

# RequestResponseStrategy

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
