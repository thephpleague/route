<?php

declare(strict_types=1);

namespace League\Route;

trait RouteCollectionTrait
{
    abstract public function map(string $method, string $path, $handler): Route;

    public function delete(string $path, $handler): Route
    {
        return $this->map('DELETE', $path, $handler);
    }

    public function get(string $path, $handler): Route
    {
        return $this->map('GET', $path, $handler);
    }

    public function head(string $path, $handler): Route
    {
        return $this->map('HEAD', $path, $handler);
    }

    public function options(string $path, $handler): Route
    {
        return $this->map('OPTIONS', $path, $handler);
    }

    public function patch(string $path, $handler): Route
    {
        return $this->map('PATCH', $path, $handler);
    }

    public function post(string $path, $handler): Route
    {
        return $this->map('POST', $path, $handler);
    }

    public function put(string $path, $handler): Route
    {
        return $this->map('PUT', $path, $handler);
    }
}
