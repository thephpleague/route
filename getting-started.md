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

In this example, the `$router` acts as a collection of routes registered with your application, and the `$dispatcher`
informs your application of which specific route's logic you'd like to execute at that time. Using the Symfony `Request` object, it is trivial to add additional routes to your application

~~~ php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$router = new League\Route\RouteCollection;

$router->addRoute('GET', '/foo/bar', function (Request $request, Response $response) {
    return $response;
});
$router->addRoute('GET', '/foo/baz', function (Request $request, Response $response) {
    return $response;
});
$router->addRoute('POST', '/foo/qux', function (Request $request, Response $response) {
    return $response;
});

$dispatcher = $router->getDispatcher();
$request = Request::createFromGlobals();

$response = $dispatcher->dispatch($request->getMethod(), $request->getPathInfo());

$response->send();
~~~

Since `$request->getPathInfo()` will return the path relative to the current script and you've already included the `HttpFoundation Request`, you get pretty URL routing essentially for free.
