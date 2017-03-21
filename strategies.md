---
layout: default
permalink: /strategies/
title: Strategies
---

# Strategies

Route strategies are a way of defining specific behaviour and controller signatures for a route.

- `League\Route\Strategy\ApplicationStrategy`
- `League\Route\Strategy\JsonStrategy`

Strategies can be set individually per route by setting it on the route.

~~~php
<?php

use League\Route\Strategy\ApplicationStrategy;
use League\Route\Strategy\JsonStrategy;

$route = new League\Route\RouteCollection;

$route->map('GET', '/acme/route', 'Acme\Controller::action')->setStrategy(new ApplicationStrategy);
$route->put('/acme/route', 'Acme\Controller::action')->setStrategy(new JsonStrategy);
~~~

By group so that the strategy will be used on any route in that group.

~~~php
<?php

use League\Route\Strategy\ApplicationStrategy;
use League\Route\Strategy\JsonStrategy;

$route = new League\Route\RouteCollection;

$route->group('/group', function ($route) {
    $route->map('GET', '/acme/route', 'Acme\Controller::action');
    $route->put('/acme/route', 'Acme\Controller::action');
})->setStrategy(new ApplicationStrategy);
~~~

Or a global strategy can be set to be used by all routes in a specific collection.

~~~php
use League\Route\Strategy\JsonStrategy;

$route = new League\Route\RouteCollection;

$route->setStrategy(new JsonStrategy);
~~~
