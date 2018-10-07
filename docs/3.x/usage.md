---
layout: post
title: Usage
sections:
    Introduction: introduction
    Hello World: hello-world
---

## Installation

It is very easy to get up and running with Route. You can use [Composer][composer]
to install and manage your installation of Route. You'll [need to install][dependencies] 
both the Route project and an implementation of the [PSR-7 message interface][psr7]. 

First, install the Route project itself:
~~~
composer require league/route
~~~

Next, install an implementation of PSR-7. We recommend the [Zend Diactoros project][diactoros].

~~~
composer require zendframework/zend-diactoros
~~~

Optionally, you could also install a PSR-11 dependency injection container, see [Dependency Injection](/4.x/dependency-injection) for more information.

~~~
composer require league/container
~~~

## Hello World

Now that we have all the packages we need, we can a simple Hello, World! application in one file.

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

[composer]: https://getcomposer.org/
[dependencies]: https://getcomposer.org/doc/01-basic-usage.md#installing-dependencies
[psr7]: https://www.php-fig.org/psr/psr-7/
[diactoros]:https://github.com/zendframework/zend-diactoros/
