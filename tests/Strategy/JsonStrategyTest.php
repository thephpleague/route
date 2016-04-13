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
     * Asserts that the strategy can build an execution chain.
     *
     * @return void
     */
    public function testStrategyCanBuildExecutionChain()
    {
        $route    = $this->getMock('League\Route\Route');
        $callable = function (ServerRequestInterface $request, ResponseInterface $response, array $args = []) {
            $response = $response->withHeader('controller', 'true');
            return $response;
        };

        $route->expects($this->once())->method('getCallable')->will($this->returnValue($callable));
        $route->expects($this->once())->method('getMiddlewareStack')->will($this->returnValue([
            new Controller, [new Controller, 'action']
        ]));

        $strategy = new JsonStrategy;
        $chain    = $strategy->getExecutionChain($route, []);

        $this->assertInstanceOf('League\Route\Middleware\ExecutionChain', $chain);

        $request  = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $response->expects($this->at(0))->method('withHeader')->with($this->equalTo('invoke'), $this->equalTo('true'))->will($this->returnSelf());
        $response->expects($this->at(1))->method('withHeader')->with($this->equalTo('action'), $this->equalTo('true'))->will($this->returnSelf());
        $response->expects($this->at(2))->method('withHeader')->with($this->equalTo('controller'), $this->equalTo('true'))->will($this->returnSelf());
        $response->expects($this->at(3))->method('withAddedHeader')->with($this->equalTo('content-type'), $this->equalTo('application/json'))->will($this->returnSelf());

        $newResponse = $chain->execute($request, $response);

        $this->assertSame($response, $newResponse);
    }

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
        $route->expects($this->once())->method('getMiddlewareStack')->will($this->returnValue([]));

        $strategy = new JsonStrategy;
        $chain    = $strategy->getExecutionChain($route, []);

        $this->assertInstanceOf('League\Route\Middleware\ExecutionChain', $chain);

        $request  = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $chain->execute($request, $response);
    }
}
