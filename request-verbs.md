---
layout: default
permalink: /request-verbs/
title: Request Verbs
---

# Request Verbs

Route has convenience methods for setting routes that will respond differently depending on the HTTP request method.

~~~php
$route = new League\Route\RouteCollection;

$route->get('/acme/route', 'Acme\Controller::getMethod');
$route->post('/acme/route', 'Acme\Controller::postMethod');
$route->put('/acme/route', 'Acme\Controller::putMethod');
$route->patch('/acme/route', 'Acme\Controller::patchMethod');
$route->delete('/acme/route', 'Acme\Controller::deleteMethod');
$route->head('/acme/route', 'Acme\Controller::headMethod');
$route->options('/acme/route', 'Acme\Controller::optionsMethod');
~~~

Each of the above routes will respond to the same URI but will invoke a different callable based on the HTTP request method.
