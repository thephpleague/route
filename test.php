<?php

declare(strict_types=1);

include __DIR__ . '/vendor/autoload.php';

$request = \Laminas\Diactoros\ServerRequestFactory::fromGlobals(
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

$router = new \League\Route\Router();

$router->get('/something', function ($request) { return []; });
$router->post('/something', function ($request) { return []; });

$router->dispatch($request);
