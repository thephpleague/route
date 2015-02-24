---
layout: default
permalink: /wildcard-routes/
title: Wildcard Routes
---

# Wildcard Routes

Wilcard routes allow a route to respond to dynamic parts of a URI. If a route has dynamic parts, they will be passed in to the controller as an associative array of arguments.

~~~ php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$router = new League\Route\RouteCollection;

$router->addRoute('GET', '/user/{id}/{name}', function (Request $request, Response $response, array $args) {
    // $args = [
    //     'id'   => {id},  // the actual value of {id}
    //     'name' => {name} // the actual value of {name}
    // ];

    return $response;
});

$dispatcher = $router->getDispatcher();

$response = $dispatcher->dispatch('GET', '/acme/1/phil');

$response->send();
~~~

Dynamic parts of a URI can also be limited to match certain requirements.

~~~ php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$router = new League\Route\RouteCollection;

// this route will only match if {id} is a number and {name} is a word
$router->addRoute('GET', '/user/{id:number}/{name:word}', function (Request $request, Response $response, array $args) {
    // do some clever shiz
    return $response;
});

$dispatcher = $router->getDispatcher();

$response = $dispatcher->dispatch('GET', '/acme/1/phil');

$response->send();
~~~

Dynamic parts can also be set as any regular expression such as `{id:[0-9]+}`.

For convenience, you can also register your own aliases for a particular regular expression using the `addPatternMatcher`
method on `RouteCollection`. For example:

~~~ php

$router = new League\Route\RouteCollection;
$router->addPatternMatcher('wordStartsWithM', '(m|M)[a-zA-Z]+');

$router->addRoute('GET', 'user/mTeam/{name:wordStartsWithM}', function (Request $request, Response $response, array $args) {
    return $response;
});
~~~

The above pattern matcher will create an internal regex string: `{$1:(m|M)[a-zA-Z]+}`, where `$1` will interpret to 'name',
 the variable listed before the colon.
