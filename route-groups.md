---
layout: default
permalink: /route-groups/
title: Route Groups
---

# Route Groups

Route groups are a way of organising your route definitions, they allow us to provide conditions and a prefix across multiple routes. As an example, this would be useful for an admin area of a website.

~~~php
<?php

$route = new League\Route\RouteCollection;

$route->group('/admin', function ($route) {
    $route->map('GET', '/acme/route1', 'AcmeController::actionOne');
    $route->map('GET', '/acme/route2', 'AcmeController::actionTwo');
    $route->map('GET', '/acme/route3', 'AcmeController::actionThree');
});
~~~

The above code will define the following routes.

~~~
GET /admin/acme/route1
GET /admin/acme/route2
GET /admin/acme/route3
~~~

Route [conditions](/route-conditions/) can be applied to a group and will be matched across all routes contained in that group, specific routes within the group can override this functionality as displayed below.

~~~php
<?php

$route = new League\Route\RouteCollection;

$route->group('/admin', function ($route) {
    $route->map('GET', '/acme/route1', 'AcmeController::actionOne');
    $route->map('GET', '/acme/route2', 'AcmeController::actionTwo')->setScheme('https');
    $route->map('GET', '/acme/route3', 'AcmeController::actionThree');
})
    ->setScheme('http')
    ->setHost('example.com')
;
~~~

The above code will define the following routes.

~~~
GET http://example.com/admin/acme/route1
GET https://example.com/admin/acme/route2
GET http://example.com/admin/acme/route3
~~~
