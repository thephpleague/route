---
layout: default
permalink: /controllers/
title: "Controllers"
---

# Controllers

Route will dispatch to any `callable` as a controller. Below are some examples.

## Class methods

~~~php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AcmeController
{
    public function someMethod(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $response;
    }
}
~~~

The class method above can be defined as a controller in a couple of ways.

~~~php
<?php

$route = new League\Route\RouteCollection;

$route->map('GET', '/acme/route', 'AcmeController::someMethod');
$route->map('GET', '/acme/route', [new AcmeController, 'someMethod']);
~~~

## Magic __invoke

~~~php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AcmeController
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $response;
    }
}
~~~

~~~php
<?php

$route = new League\Route\RouteCollection;

$route->map('GET', '/acme/route', new AcmeController);
~~~

Anonymous functions

~~~php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$route = new League\Route\RouteCollection;

$route->map('GET', '/acme/route', function (ServerRequestInterface $request, ResponseInterface $response) {
    return $response;
});
~~~

Named functions

~~~php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$route = new League\Route\RouteCollection;

function controller(ServerRequestInterface $request, ResponseInterface $response) {
    return $response;
}

$route->map('GET', '/acme/route', 'controller');
~~~
