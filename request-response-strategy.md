---
layout: default
permalink: /request-response-strategy/
title: RequestResponseStrategy
---

# RequestResponseStrategy

The `RequestResponseStrategy` is used by default and provides the controller with both the `Request` and `Response` objects. The idea here being that you can pull any information you need from the `Request`, manipulate the `Response` and return it for the dispatcher to send to the browser. The dispatcher will throw a `RuntimeException` if the controller it is invoking does not return an instance of the `Response` object.

~~~ php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$route->get('/acme/route', function (Request $request, Response $response) {
    // retrieve data from $request, do what you need to do and build your $content

    $response->setContent($content);
    $response->setStatusCode(200);

    return $response;
});
~~~

~~~ php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$route->put('/user/{id}', function (Request $request, Response $response, array $args) {
    $userId = $args['id'];
    $requestBody = json_decode($request->getContent(), true);

    // possibly update a record in the database with the request body

    $response->setContent('Updated User with ID: ' . $userId);
    $response->setStatusCode(202);

    return $response;
});
~~~

Whilst these are primitive and naive examples, it is good design to handle your request and response lifecycle in this way as you are fully in control of input and output.
