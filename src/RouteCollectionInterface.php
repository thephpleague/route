<?php declare(strict_types=1);

namespace League\Route;

interface RouteCollectionInterface
{
    /**
     * Add a route to the map
     *
     * @param string          $method
     * @param string          $path
     * @param callable|string $handler
     *
     * @return Route
     */
    public function map(string $method, string $path, $handler): Route;

    /**
     * Add a route that responds to GET HTTP method
     *
     * @param string          $path
     * @param callable|string $handler
     *
     * @return Route
     */
    public function get($path, $handler);

    /**
     * Add a route that responds to POST HTTP method
     *
     * @param string          $path
     * @param callable|string $handler
     *
     * @return Route
     */
    public function post($path, $handler);

    /**
     * Add a route that responds to PUT HTTP method
     *
     * @param string          $path
     * @param callable|string $handler
     *
     * @return Route
     */
    public function put($path, $handler);

    /**
     * Add a route that responds to PATCH HTTP method
     *
     * @param string          $path
     * @param callable|string $handler
     *
     * @return Route
     */
    public function patch($path, $handler);

    /**
     * Add a route that responds to DELETE HTTP method
     *
     * @param string          $path
     * @param callable|string $handler
     *
     * @return Route
     */
    public function delete($path, $handler);

    /**
     * Add a route that responds to HEAD HTTP method
     *
     * @param string          $path
     * @param callable|string $handler
     *
     * @return Route
     */
    public function head($path, $handler);

    /**
     * Add a route that responds to OPTIONS HTTP method
     *
     * @param string          $path
     * @param callable|string $handler
     *
     * @return Route
     */
    public function options($path, $handler);
}
