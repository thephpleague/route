<?php

namespace League\Route\Test\Strategy;

use League\Route\Http\Exception\BadRequestException;
use League\Route\Strategy\JsonStrategy;
use League\Route\Test\Asset\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class JsonStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the strategy builds a json response for a controller that does not return a repsonse.
     *
     * @return void
     */
    public function testStrategyBuildsJsonErrorResponseWhenNoResponseReturned()
    {
        $this->setExpectedException('RuntimeException');

        $route    = $this->getMock('League\Route\Route');
        $callable = function (ServerRequestInterface $request, ResponseInterface $response, array $args = []) {};

        $route->expects($this->once())->method('getCallable')->will($this->returnValue($callable));

        $strategy = new JsonStrategy;
        $callable = $strategy->getCallable($route, []);

        $request  = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $next = function ($request, $response) {
            return $response;
        };

        $callable($request, $response, $next);
    }
}
