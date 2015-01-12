---
layout: default
permalink: /method-argument-strategy/
title: MethodArgumentStrategy
---

# MethodArgumentStrategy

The Method Argument Strategy allows you to have your controller dependencies injected directly as arguments.

~~~ php
$route->get('/some-route', function (SomeDependency $SomeDependency, SomeOtherDependency $someOtherDependency) {
    // ...
});
~~~

The router will interact the [League\Container](https://github.com/thephpleage/container), if the controller is a class method, it will check for a definition within the container, if there is no definition or the controller is some other type of `callable`, will attempt to automatically resolve the dependencies.

The response is handled in the same way as the [UriStrategy](/uri-strategy/).

For details on how to define dependencies for your controller, please see the [documentation](http://container.thephpleague.com/registering-callables/) for League\Container.
