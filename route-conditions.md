---
layout: default
permalink: /route-conditions/
title: Route Conditions
---

# Route Conditions

There are times when you may wish to add further conditions to route matching other than the request verb and URI. Route allows this by chaining further conditions to the route definition.

~~~php
<?php

$route = new League\Route\RouteCollection;

// this route will respond to http://example.com/acme/route
// or https://example.com/acme/route
$route->map('GET', '/acme/route', 'AcmeController::method')->setHost('example.com');

// this route will only respond to https://example.com/acme/route
$route->map('GET', '/acme/route', 'AcmeController::method')->setScheme('https')->setHost('example.com');
~~~

Conditions can also be applied across [route groups](/route-groups/).
