---
layout: default
permalink: /request-verbs/
title: Request Verbs
---

# Request Verbs

The router has convenience methods for setting routes that will respond differently depending on the HTTP request method.

~~~ php
$router = new League\Route\RouteCollection;

$router->get('/acme/route', 'Acme\Controller::getMethod');
$router->post('/acme/route', 'Acme\Controller::postMethod');
$router->put('/acme/route', 'Acme\Controller::putMethod');
$router->patch('/acme/route', 'Acme\Controller::patchMethod');
$router->delete('/acme/route', 'Acme\Controller::deleteMethod');
$router->head('/acme/route', 'Acme\Controller::headMethod');
$router->options('/acme/route', 'Acme\Controller::optionsMethod');
~~~

Each of the above routes will respond to the same URI but will invoke a different callable based on the HTTP request method.
