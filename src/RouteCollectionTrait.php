<?php declare(strict_types=1);

namespace League\Route;

trait RouteCollectionTrait
{
    /**
     * Add a route to the map.
     *
     * @param string          $method
     * @param string          $path
     * @param callable|string $handler
     *
     * @return Route
     */
    abstract public function map(string $method, string $path, $handler);

    /**
     * Add a route that responds to GET HTTP method.
     *
     * @param string          $path
     * @param callable|string $handler
     *
     * @return Route
     */
    public function get($path, $handler)
    {
        return $this->map('GET', $path, $handler);
    }

    /**
     * Add a route that responds to POST HTTP method.
     *
     * @param string          $path
     * @param callable|string $handler
     *
     * @return Route
     */
    public function post($path, $handler)
    {
        return $this->map('POST', $path, $handler);
    }

    /**
     * Add a route that responds to PUT HTTP method.
     *
     * @param string          $path
     * @param callable|string $handler
     *
     * @return Route
     */
    public function put($path, $handler)
    {
        return $this->map('PUT', $path, $handler);
    }

    /**
     * Add a route that responds to PATCH HTTP method.
     *
     * @param string          $path
     * @param callable|string $handler
     *
     * @return Route
     */
    public function patch($path, $handler)
    {
        return $this->map('PATCH', $path, $handler);
    }

    /**
     * Add a route that responds to DELETE HTTP method.
     *
     * @param string          $path
     * @param callable|string $handler
     *
     * @return Route
     */
    public function delete($path, $handler)
    {
        return $this->map('DELETE', $path, $handler);
    }

    /**
     * Add a route that responds to HEAD HTTP method.
     *
     * @param string          $path
     * @param callable|string $handler
     *
     * @return Route
     */
    public function head($path, $handler)
    {
        return $this->map('HEAD', $path, $handler);
    }

    /**
     * Add a route that responds to OPTIONS HTTP method.
     *
     * @param string          $path
     * @param callable|string $handler
     *
     * @return Route
     */
    public function options($path, $handler)
    {
        return $this->map('OPTIONS', $path, $handler);
    }
}
