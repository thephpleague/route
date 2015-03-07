<?php

namespace League\Route;

use FastRoute\RouteParser as IRouteParser;
use FastRoute\RouteParser\Std;

/**
 * Parses routes of the following form:
 *
 * "/user/{name}/{id:[0-9]+}"
 */
class RouteParser extends Std implements IRouteParser
{
    /**
     * Regex to find the route alias
     */
    const ALIAS_REGEX = '/^(@[a-zA-Z0-9-_\.]+)/';
    
    /**
     * Parses the string into an array of segments
     *
     * @param string $route
     * @return array
     */
    public function parse($route)
    {
        
        //Remove possible name in route
        $route = preg_replace(self::ALIAS_REGEX, '', $route);
        
        return parent::parse($route);
    }
}
