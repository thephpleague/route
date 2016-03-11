---
layout: default
permalink: /strategies/
title: Strategies
---

# Strategies

Route strategies are a way of defining specific behaviour and controller signatures for a route.

- `League\Route\Strategy\RequestResponseStrategy`
- `League\Route\Strategy\ParamStrategy`
- `League\Route\Strategy\JsonStrategy`

Strategies can be set individually per route by setting it on the route.

~~~php
<?php

use League\Route\Strategy\RequestResponseStrategy;
use League\Route\Strategy\ParamStrategy;
use League\Route\Strategy\JsonStrategy;

$route = new League\Route\RouteCollection;

$route->map('GET', '/acme/route', 'Acme\Controller::action')->setStrategy(new RequestResponseStrategy);
$route->get('/acme/route', 'Acme\Controller::action')->setStrategy(new ParamStrategy);
$route->put('/acme/route', 'Acme\Controller::action')->setStrategy(new JsonStrategy);
~~~

Or a global strategy can be set to be used by all routes in a specific collection.

~~~php
use League\Route\Strategy\JsonStrategy;

$route = new League\Route\RouteCollection;

$route->setStrategy(new JsonStrategy);
~~~

## Custom Strategies

Route allows you to define a custom dispatch strategy by implementing `League\Route\Strategy\StrategyInterface`.

~~~php
<?php

namespace Acme\Strategy;

use League\Route\Strategy\StrategyInterface;

class CustomStrategy implements StrategyInterface
{
    public function dispatch($controller, array $vars)
    {
        // ... handle the dispatch of the controller yourself
    }
}
~~~

~~~php
use Acme\Strategy\CustomStrategy;

$route = new League\Route\RouteCollection;

$route->setStrategy(new CustomStrategy);
~~~

Now when the route is dispatched, the `dispatch` method of the custom strategy will be invoked and passed arguments needed to invoke a controller.

The `$controller` argument will be one of three types, `string` (points to a named function), `array` (points to a class method `[0 => 'ClassName', 1 => 'methodName']`) or `\Closure` (is an anonymous function), and the `$vars` argument is an associative array of wildcard segments from the matched route `['wildcard' => 'actual_value']`.

The return of your dispatch method will bubble out and be returned by `League\Route\Dispatcher::dispatch`, it does not require a return value, however, you should be aware that there is no output buffering within the dispatch process by default.
