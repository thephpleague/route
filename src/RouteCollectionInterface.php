<?php

declare(strict_types=1);

namespace League\Route;

use Psr\Http\Server\RequestHandlerInterface;

interface RouteCollectionInterface
{
    /**
     * @param callable|array<string>|string|RequestHandlerInterface $handler
     */
    public function delete(string $path, callable|array|string|RequestHandlerInterface $handler): Route;

    /**
     * @param callable|array<string>|string|RequestHandlerInterface $handler
     */
    public function get(string $path, callable|array|string|RequestHandlerInterface $handler): Route;

    /**
     * @param callable|array<string>|string|RequestHandlerInterface $handler
     */
    public function head(string $path, callable|array|string|RequestHandlerInterface $handler): Route;

    /**
     * @param array<string>|string $method
     * @param callable|array<string>|string|RequestHandlerInterface $handler
     */
    public function map(
        string|array $method,
        string $path,
        callable|array|string|RequestHandlerInterface $handler
    ): Route;

    /**
     * @param callable|array<string>|string|RequestHandlerInterface $handler
     */
    public function options(string $path, callable|array|string|RequestHandlerInterface $handler): Route;

    /**
     * @param callable|array<string>|string|RequestHandlerInterface $handler
     */
    public function patch(string $path, callable|array|string|RequestHandlerInterface $handler): Route;

    /**
     * @param callable|array<string>|string|RequestHandlerInterface $handler
     */
    public function post(string $path, callable|array|string|RequestHandlerInterface $handler): Route;

    /**
     * @param callable|array<string>|string|RequestHandlerInterface $handler
     */
    public function put(string $path, callable|array|string|RequestHandlerInterface $handler): Route;
}
