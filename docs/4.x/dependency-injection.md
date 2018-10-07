---
layout: post
title: Dependency Injection
sections:
    Introduction: introduction
    Recommended Reading: recommended-reading
    Using a Container: using-a-container
---
## Introduction

Route has the ability to use a [PSR-11](https://www.php-fig.org/psr/psr-11/) dependency injection container to resolve any classes it needs to instantiate. Using a dependency injection container is no longer forced with route, however, it is very much recommended.

## Recommended Reading

It is recommended that if you have limited or no knowledge of dependency injection you should read about the concepts before you attempt to implement it. A good place to get started is with the [Dependency Injection chapter](https://www.phptherightway.com/#dependency_injection) of PHP The Right Way.

## Using a Container

In these examples, we will be using [league/container](https://container.thephpleague.com/) to demonstrate how to easily implement a dependency injection container with route.

Consider that we have a controller class that needs a template renderer to load and render our HTML templates.

~~~php
<?php declare(strict_types=1);

namespace Acme;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;

class SomeController
{
    /**
     * @var \Acme\TemplateRenderer
     */
    protected $templateRenderer;

    /**
     * Construct.
     *
     * @param \Acme\TemplateRenderer $templateRenderer
     */
    public function __construct(TemplateRenderer $templateRenderer)
    {
        $this->templateRenderer = $templateRenderer;
    }

    /**
     * Controller.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request) : ResponseInterface
    {
        $body     = $this->templateRenderer->render('some-template');
        $response = new Response;

        $response->getBody()->write($body);
        return $response->withStatus(200);
    }
}
~~~

We can now build a container, define our controller and set it on the strategy, when the route is matched, the controller will be resolved via the container with the template renderer passed to it.

~~~php
<?php declare(strict_types=1);

$container = new League\Container\Container;

$container->add(Acme\SomeController::class)->addArgument(Acme\TemplateRenderer::class);
$container->add(Acme\TemplateRenderer::class);

$strategy = (new League\Route\Strategy\ApplicationStrategy)->setContainer($container);
$router   = (new League\Route\Router)->setStrategy($strategy);

$router->map('GET', '/', Acme\SomeController::class);
~~~
