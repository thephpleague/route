---
layout: default
permalink: /wildcard-routes/
title: Wildcard Routes
---

# Wildcard Routes

Wilcard routes allow a route to respond to dynamic parts of a URI. If a route has dynamic parts, they will be passed in to the controller as an associative array of arguments.

~~~php
<?php

$router = new League\Route\RouteCollection;

$router->map('GET', '/user/{id}/{name}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) {
    // $args = [
    //     'id'   => {id},  // the actual value of {id}
    //     'name' => {name} // the actual value of {name}
    // ];

    return $response;
});
~~~

Dynamic parts of a URI can also be limited to match certain requirements.

~~~php
<?php

$router = new League\Route\RouteCollection;

// this route will only match if {id} is numeric and {name} is a alpha
$router->map('GET', '/user/{id:number}/{name:word}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) {
    // $args = [
    //     'id'   => {id},  // the actual value of {id}
    //     'name' => {name} // the actual value of {name}
    // ];

    return $response;
});
~~~

There are several built in conditions for dynamic parts of a URI.

- `number`
- `word`
- `alphanum_dash`
- `slug`
- `uuid`

Dynamic parts can also be set as any regular expression such as `{id:[0-9]+}`.

For convenience, you can also register your own aliases for a particular regular expression using the `addPatternMatcher` method on `RouteCollection`. For example:

~~~php
<?php

$router = new League\Route\RouteCollection;

$router->addPatternMatcher('wordStartsWithM', '(m|M)[a-zA-Z]+');

$router->map('GET', 'user/mTeam/{name:wordStartsWithM}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) {
    // $args = [
    //     'id'   => {id},  // the actual value of {id}
    //     'name' => {name} // the actual value of {name}
    // ];

    return $response;
});
~~~

The above pattern matcher will create an internal regular expression string: `{$1:(m|M)[a-zA-Z]+}`, where `$1` will interpret to 'name', the variable listed before the colon.
