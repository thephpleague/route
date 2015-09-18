<?php

namespace League\Route;

interface RouteCollectionInterface
{
    /**
     * Add a route to the map.
     *
     * @param array|string $method
     * @param string       $path
     * @param callable     $handler
     *
     * @return \League\Route\Route
     */
    public function map($method, $path, callable $handler);

    /**
     * Add a route that responds to GET HTTP method.
     *
     * @param string   $path
     * @param callable $handler
     *
     * @return \League\Route\Route
     */
    public function get($path, callable $handler);

    /**
     * Add a route that responds to POST HTTP method.
     *
     * @param string   $path
     * @param callable $handler
     *
     * @return \League\Route\Route
     */
    public function post($path, callable $handler);

    /**
     * Add a route that responds to PUT HTTP method.
     *
     * @param string   $path
     * @param callable $handler
     *
     * @return \League\Route\Route
     */
    public function put($path, callable $handler);

    /**
     * Add a route that responds to PATCH HTTP method.
     *
     * @param string   $path
     * @param callable $handler
     *
     * @return \League\Route\Route
     */
    public function patch($path, callable $handler);

    /**
     * Add a route that responds to DELETE HTTP method.
     *
     * @param string   $path
     * @param callable $handler
     *
     * @return \League\Route\Route
     */
    public function delete($path, callable $handler);

    /**
     * Add a route that responds to HEAD HTTP method.
     *
     * @param string   $path
     * @param callable $handler
     *
     * @return \League\Route\Route
     */
    public function head($path, callable $handler);

    /**
     * Add a route that responds to OPTIONS HTTP method.
     *
     * @param string   $path
     * @param callable $handler
     *
     * @return \League\Route\Route
     */
    public function options($path, callable $handler);
}
