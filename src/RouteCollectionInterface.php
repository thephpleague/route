<?php

declare(strict_types=1);

namespace League\Route;

interface RouteCollectionInterface
{
    public function delete(string $path, $handler): Route;
    public function get(string $path, $handler): Route;
    public function head(string $path, $handler): Route;
    public function map(string $method, string $path, $handler): Route;
    public function options(string $path, $handler): Route;
    public function patch(string $path, $handler): Route;
    public function post(string $path, $handler): Route;
    public function put(string $path, $handler): Route;
}
