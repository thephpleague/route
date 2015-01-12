---
layout: default
permalink: /uri-strategy/
title: UriStrategy
---

# UriStrategy

The URI Strategy is a simpler strategy aimed at smaller applications. It makes no assumptions about how your controller is built. The only arguments passed to your controller will be the values of any wildcard parts of your routes string if any exist. It expects a value to be returned but this can any type of `Symfony\Component\HttpFoundation\Response` based object that can be sent to the browser or a string that a response can be built from.

~~~ php
$route->get('/hello/{name1}/{name2}', function ($name1, $name2) {
    return '<h1>Hello ' . $name1 . ' and ' . $name2 . '</h1>';
});
~~~
