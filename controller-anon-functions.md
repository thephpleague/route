---
layout: default
permalink: /controller-anon-functions/
title: "Controller: Anonymous Function/Closures"
---

# Controller: Anonymous Function/Closures

You may wish to build a micro app using anonymous functions as the controllers.

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
