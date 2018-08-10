---
layout: post
title: Usage
sections:
    Hello World: hello-world
---
## Hello World

It is very easy to get up and running with Route. This guide will help you create a simple "Hello, World!" application.

Firstly you need to look at our [installation guide](/2.x/#installation). You will also need to install an implementation of PSR-7.

~~~
composer require league/route
~~~

~~~
composer require zendframework/zend-diactoros
~~~

~~~php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$container = new League\Container\Container;

$container->share('response', Zend\Diactoros\Response::class);
$container->share('request', function () {
    return Zend\Diactoros\ServerRequestFactory::fromGlobals(
        $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES
    );
});

$container->share('emitter', Zend\Diactoros\Response\SapiEmitter::class);

$route = new League\Route\RouteCollection($container);

$route->map('GET', '/', function (ServerRequestInterface $request, ResponseInterface $response) {
    $response->getBody()->write('<h1>Hello, World!</h1>');

    return $response;
});

$response = $route->dispatch($container->get('request'), $container->get('response'));

$container->get('emitter')->emit($response);
~~~
