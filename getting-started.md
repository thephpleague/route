---
layout: default
permalink: /getting-started/
title: Getting Started
---

# Getting Started

By default when dispatching your controllers, Route will employ the `RequestResponseStrategy` (more on strategies in the menu to the left).

This strategy will provide you with a request and response object with which you can pull data from the request, manipulate the response and return it.

~~~ php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$router = new League\Route\RouteCollection;

$router->addRoute('GET', '/acme/route', function (Request $request, Response $response) {
    // do something clever
    return $response;
});

$dispatcher = $router->getDispatcher();

$response = $dispatcher->dispatch('GET', '/acme/route');

$response->send();
~~~
