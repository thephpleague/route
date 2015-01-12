---
layout: default
permalink: /controller-class-methods/
title: "Controller: Class Methods"
---

# Controller: Class Methods

Using a class method as a controller is simple. Just point the router to that method.

~~~ php
namespace Acme;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Controller
{
    public function action (Request $request, Response $response)
    {
        // do something clever
        return $response;
    }
}
~~~

~~~ php
$router = new League\Route\RouteCollection;

$router->addRoute('GET', '/acme/route', 'Acme\Controller::action'); // Classname::methodName

$dispatcher = $router->getDispatcher();

$response = $dispatcher->dispatch('GET', '/acme/route');

$response->send();
~~~

## Dependency Injection

Controller classes are resolved through [League\Container](https://github.com/thephpleague/container) so if your class has shared dependencies between methods you can have said dependencies injected in to the class contructor. For more information on using [League\Container](https://github.com/thephpleague/container), check out the [documentation](http://container.thephpleague.com).

Once you have a configured Container, it is as simple as injecting it in to the `RouteCollection`.

~~~ php
$container = new League\Container\Container;
// ... set up the container

$router = new League\Route\RouteCollection($container);
// ... handle routing
~~~
