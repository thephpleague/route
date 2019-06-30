---
layout: post
title: Controllers
sections:
    Introduction: introduction
    Defining Controllers: defining-controllers
    Types of Controllers: types-of-controllers
    Dependency Injection: dependency-injection
---
## Introduction

Every defined route requires a `callable` to invoke when dispatched, something that could be described as a controller in MVC. By default, Route only imposes that the callable is defined with a specific signature, it is given a request object as the first argument, an associative array of wildcard route arguments as the second argument, and expects a response object to be returned. Read more about this in [HTTP](/4.x/http).

This behaviour can be changed by creating/using a different strategy, read more about strategies [here](/4.x/strategies).

## Defining Controllers

Defining what controller is invoked when a route is matched is as easy as padding a callable as the the third argument of the `map` method or the second argument of the proxy methods for different request verbs, `get`, `post` etc.

~~~php
<?php declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$router = new League\Route\Router;

$router->map('GET', '/route', function (ServerRequestInterface $request) : ResponseInterface {
    // ...
});

$router->get('/another-route', function (ServerRequestInterface $request) : ResponseInterface {
    // ...
});
~~~

## Types of Controllers

As mentioned above, Route will dispatch any `callable` when a route is matched.

For performance reasons, Route also allows you to define controllers as a type of proxy, there are two of these proxies that will allow you to define strings and the actually callable will be built when Route dispatches it.

### Closure

A controller can be defined as a simple `\Closure` anonymous function.

~~~php
<?php declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$router = new League\Route\Router;

$router->map('GET', '/', function (ServerRequestInterface $request) : ResponseInterface {
    // ...
});
~~~

### Lazy Loaded Class Method (Proxy)

You can define a class method as a controller where the callable is to be lazy loaded when it is dispatched by defining a string and separating the class name and method name like so `ClassName::methodName`.

~~~php
<?php declare(strict_types=1);

namespace Acme;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SomeController
{
    /**
     * Controller.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function someMethod(ServerRequestInterface $request) : ResponseInterface
    {
        // ...
    }
}
~~~

~~~php
<?php declare(strict_types=1);

$router = new League\Route\Router;

$router->map('GET', '/', 'Acme\SomeController::someMethod');
~~~

### Lazy Loaded Class Implementing `__invoke` (Proxy)

You can define the name of a class that implements the magic `__invoke` method and the object will not be instantiated until it is dispatched.

~~~php
<?php declare(strict_types=1);

namespace Acme;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SomeController
{
    /**
     * Controller.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request) : ResponseInterface
    {
        // ...
    }
}
~~~

~~~php
<?php declare(strict_types=1);

$router = new League\Route\Router;

$router->map('GET', '/', Acme\SomeController::class);
~~~

### Lazy Loaded Array Based Callable (Proxy)

A controller can be defined as an array based callable where the class element will not be instantiated until it is dispatched.

~~~php
<?php declare(strict_types=1);

namespace Acme;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SomeController
{
    /**
     * Controller.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function someMethod(ServerRequestInterface $request) : ResponseInterface
    {
        // ...
    }
}
~~~

~~~php
<?php declare(strict_types=1);

$router = new League\Route\Router;

$router->map('GET', '/', [Acme\SomeController::class, 'someMethod']);
~~~

### Object Implementing `__invoke`

~~~php
<?php declare(strict_types=1);

namespace Acme;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SomeController
{
    /**
     * Controller.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request) : ResponseInterface
    {
        // ...
    }
}
~~~

~~~php
<?php declare(strict_types=1);

$router = new League\Route\Router;

$router->map('GET', '/', new Acme\SomeController);
~~~

### Array Based Callable

~~~php
<?php declare(strict_types=1);

namespace Acme;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SomeController
{
    /**
     * Controller.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function someMethod(ServerRequestInterface $request) : ResponseInterface
    {
        // ...
    }
}
~~~

~~~php
<?php declare(strict_types=1);

$router = new League\Route\Router;

$router->map('GET', '/', [new Acme\SomeController, 'someMethod']);
~~~

### Named Function

~~~php
<?php declare(strict_types=1);

namespace Acme;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller.
 *
 * @param \Psr\Http\Message\ServerRequestInterface $request
 *
 * @return \Psr\Http\Message\ResponseInterface
 */
function controller(ServerRequestInterface $request) : ResponseInterface
{
    // ...
}
~~~

~~~php
<?php declare(strict_types=1);

$router = new League\Route\Router;

$router->map('GET', '/', 'Acme\controller');
~~~

## Dependency Injection

Where Route is instantiating the objects for your defined controller, a dependency injection container can be used to resolve those objects. Read more on dependency injection [here](/docs/4.x/dependency-injection/).
