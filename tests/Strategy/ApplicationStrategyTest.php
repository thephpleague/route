<?php

namespace League\Route\Test\Strategy;

use League\Route\Strategy\ApplicationStrategy;
use League\Route\Test\Asset\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ApplicationStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the strategy builds a middleware that throws an exception when no response is returned.
     *
     * @return void
     */
    public function testStrategyBuildsMiddlewareToThrowExceptionWhenNoResponseReturned()
    {
        $this->setExpectedException('RuntimeException');

        $route    = $this->getMock('League\Route\Route');
        $callable = function (ServerRequestInterface $request, ResponseInterface $response, array $args = []) {};

        $route->expects($this->once())->method('getCallable')->will($this->returnValue($callable));

        $strategy = new ApplicationStrategy;
        $callable = $strategy->getCallable($route, []);

        $request  = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $next = function ($request, $response) {
            return $response;
        };

        $callable($request, $response, $next);
    }
}
