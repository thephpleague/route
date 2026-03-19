---
layout: post
title: Getting Started
sections:
    What is Route?: what-is-route
    Goals: goals
    Questions?: questions
    Installation: installation
---
[![Author](https://img.shields.io/badge/author-@philipobenito-blue.svg?style=flat-square)](https://twitter.com/philipobenito)
[![Latest Version](https://img.shields.io/github/release/thephpleague/route.svg?style=flat-square)](https://github.com/thephpleague/route/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://github.com/thephpleague/route/blob/master/LICENSE.md)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/thephpleague/route.svg?style=flat-square)](https://scrutinizer-ci.com/g/thephpleague/route/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/thephpleague/route.svg?style=flat-square)](https://scrutinizer-ci.com/g/thephpleague/route)
[![Total Downloads](https://img.shields.io/packagist/dt/league/route.svg?style=flat-square)](https://packagist.org/packages/league/route)

## What is Route?

Route is a fast PSR-7 routing and dispatcher package including PSR-15 middleware implementation that enables you to build well designed performant web apps.

At its core is Nikita Popov's [FastRoute](https://github.com/nikic/FastRoute) package allowing this package to concentrate on the dispatch of your controllers.

[Route on Packagist](https://packagist.org/packages/league/route)

## Goals

- To provide a "friendlier" API on top of [FastRoute](https://github.com/nikic/FastRoute).
- To provide an easy interface to implement PSR-7 HTTP messages in to your applications.
- To enable you to implement PSR-15 middleware in to your applications.
- To provide route introspection via the `RouterInterface` for advanced use cases.
- To enable non-blocking route matching with the `match()` method.
- To provide convenience in building web applications and APIs.

## Questions?

Route was created by Phil Bennett. Find him on Twitter at [@philipobenito](https://twitter.com/philipobenito).

## Installation

## System Requirements

You need `PHP >= 8.2.0` to use `League\Route` but the latest stable version of PHP is recommended.

You will also require an implementation of PSR-7 HTTP Message. Throughout the documentation we will be using the [Laminas Diactoros Project](https://github.com/laminas/laminas-diactoros/), however, there are many implementations to choose from on [Packagist](https://packagist.org/providers/psr/http-message-implementation).

You may also want to use a PSR-11 dependency injection container and a PSR-17 HTTP factory implementation for enhanced functionality.

### Composer

Route is available on [Packagist](https://packagist.org/packages/league/route) and can be installed using [Composer](https://getcomposer.org/):

~~~
composer require league/route
~~~

Most modern frameworks will include Composer out of the box, but ensure the following file is included:

~~~php
<?php

require 'vendor/autoload.php';
~~~

## Upgrading

If you are upgrading from a previous version of Route, see the [upgrade guide](/unstable/upgrade).
