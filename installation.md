---
layout: default
permalink: installation/
title: Installation
---

Installation
============

## System Requirements

You need **PHP >= 5.4.0** to use `League\Route` but the latest stable version of PHP is recommended.

## Composer

Route is available on [Packagist](https://packagist.org/packages/league/route) and can be installed using [Composer](https://getcomposer.org/):

~~~
composer install league/route
~~~

Most modern frameworks will include Composer out of the box, but ensure the following file is included:

~~~ php
<?php

// include the Composer autoloader
require 'vendor/autoload.php';
~~~

## Going Solo

You can also use Route without using Composer by registering an autoloader function:

~~~ php
spl_autoload_register(function ($class) {
    $prefix = 'League\\Route\\';
    $base_dir = __DIR__ . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});
~~~

Or, use any other [PSR-4](http://www.php-fig.org/psr/psr-4/) compatible autoloader.
