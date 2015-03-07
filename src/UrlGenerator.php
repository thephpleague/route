<?php

namespace League\Route;

use League\Route\RouteCollection;
use FastRoute\RouteParser as IRouteParser;
use FastRoute\RouteParser\Std as StdRouteParser;

/**
 * Generates an URL from a RouteCollection
 *
 * @author Sonia Marquette <contact@nebulousweb.com>
 */
class UrlGenerator
{

    /**
     * Route collection to use
     *
     * @var RouteCollection
     */
    protected $routes;

    /**
     * Route Parser to use
     * @var RouteParser
     */
    protected $parser;

    /**
     * The base to which urls are appended
     * @var string
     */
    protected $baseUrl = '';

    public function __construct(RouteCollection $routes, IRouteParser $parser = null)
    {
        $this->routes = $routes;
        $this->parser = is_null($parser) ? new StdRouteParser : $parser;
    }

    /**
     * Set the base url to use when generating new urls
     * @param string $baseUrl
     * @return \League\Route\UrlGenerator
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    /**
     * Generates an url using a registered route
     *
     * @param string $name Route Alias
     * @param array $params
     * @return string
     * @throws \OutOfBoundsException
     * @throws \InvalidArgumentException
     */
    public function generate($name, $params = array(), $include_base = true)
    {
        //Ensure the names starts with @
        $alias = strpos($name, '@') === false ? '@' . $name : $name;

        $routes = $this->routes->getNamedRoutes();

        //Verify the route exists
        if (!isset($routes[$alias])) {
            throw new \OutOfBoundsException(sprintf('The named route %s does not exist.', $alias));
        }

        //Parse route into array of parameters to build the new url
        $parsed_route = $this->parser->parse($routes[$alias]);
        $url = '';

        foreach ($parsed_route as $part) {
            if (is_string($part)) {
                $url .= $part;
            } elseif (is_array($part) && isset($params[$part[0]]) && preg_match('#' . $part[1] . '#', $params[$part[0]])) {
                $url .= $params[$part[0]];
            } elseif (isset($params[$part[0]])) {
                throw new \InvalidArgumentException(sprintf('Invalid value %s for parameter %s for route %s. Does not match the pattern: %s', $params[$part[0]], $part[0], $alias, $part[1]));
            } else {
                throw new \InvalidArgumentException(sprintf('Missing parameter %s for route %s.', $part[0], $alias));
            }
        }

        return ($include_base && !empty($this->baseUrl) ? rtrim($this->baseUrl, '/') . '/' : '' ) . ltrim($url, '/');
    }
}
