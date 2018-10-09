---
layout: post
title: Usage
sections:
    Introduction: introduction
    Hello, World!: hello-world
    APIs: apis
---
## Introduction

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

## Hello, World!

Now that we have all the packages we need, we can a simple Hello, World! application in one file.

~~~php
<?php declare(strict_types=1);

include 'path/to/vendor/autoload.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$request = Zend\Diactoros\ServerRequestFactory::fromGlobals(
    $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES
);

$router = new League\Route\Router;

// map a route
$router->map('GET', '/', function (ServerRequestInterface $request) : ResponseInterface {
    $response = new Zend\Diactoros\Response;
    $response->getBody()->write('<h1>Hello, World!</h1>');
    return $response;
});

$response = $router->dispatch($request);

// send the response to the browser
(new Zend\Diactoros\Response\SapiEmitter)->emit($response);
~~~

## APIs

Only a few changes are needed to create a simple JSON API. We have to change the strategy that the router uses to dispatch a controller, as well as providing a response factory to ensure the JSON Strategy can build the response it needs to.

To provide a response factory, we will need to install a http-interop response factory package, in this case we will use the factory for zend-diactoros.

~~~
composer require http-interop/http-factory-diactoros
~~~

~~~php
<?php declare(strict_types=1);

include 'path/to/vendor/autoload.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$request = Zend\Diactoros\ServerRequestFactory::fromGlobals(
    $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES
);

$responseFactory = new Http\Factory\Diactoros\ResponseFactory;

$strategy = new League\Route\Strategy\JsonStrategy($responseFactory);
$router   = (new League\Route\Router)->setStrategy($strategy);

// map a route
$router->map('GET', '/', function (ServerRequestInterface $request) : array {
    return [
        'title'   => 'My New Simple API',
        'version' => 1,
    ];
});

$response = $router->dispatch($request);

// send the response to the browser
(new Zend\Diactoros\Response\SapiEmitter)->emit($response);
~~~

The code above will build turn your returned array in to a JSON response.

~~~json
{
    "title": "My New Simple API",
    "version": 1
}
~~~

[composer]: https://getcomposer.org/
[dependencies]: https://getcomposer.org/doc/01-basic-usage.md#installing-dependencies
[psr7]: https://www.php-fig.org/psr/psr-7/
[diactoros]:https://github.com/zendframework/zend-diactoros/
