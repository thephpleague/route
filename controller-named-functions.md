---
layout: default
permalink: /controller-named-functions/
title: "Controller: Named Function"
---

# Controller: Named Function

If you prefer to name your functions, you can.

~~~ php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

function controller (Request $request, Response $response) {
    // do something clever
    return $response
}

$router = new League\Route\RouteCollection;

$router->addRoute('GET', '/acme/route', 'controller');

$dispatcher = $router->getDispatcher();

$response = $dispatcher->dispatch('GET', '/acme/route');

$response->send();
~~~
