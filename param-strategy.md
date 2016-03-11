---
layout: default
permalink: /param-strategy/
title: Parameter Strategy
---

# Parameter Strategy

The parameter strategy uses reflection via [league/container](http://container.thephpleague.com) to invoke your controller and pass in any route parameters along with any type hinted dependencies on the method.

~~~php
<?php

use Psr\Http\Message\ServerRequestInterface;

$route = new League\Route\RouteCollection;

$route->setStrategy(new League\Route\Strategy\ParamStrategy);

$route->get('/hello/{name1}/{name2}', function (ServerRequestInterface $request, $name1, $name2) {
    return '<h1>Hello ' . $name1 . ' and ' . $name2 . '</h1>';
});
~~~

When the above controller is invoked, the strategy will reflect on it's parameters, attempt to resolve `ServerRequestInterface` from the container, and pass the dynamic parts of the route to the corresponding parameter names.
