<?php

namespace League\Route\Test\Strategy;

use League\Route\Strategy\ApplicationStrategy;
use League\Route\Test\Asset\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ApplicationStrategyTest extends \PHPUnit_Framework_TestCase
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

        $strategy = new ApplicationStrategy;
        $chain    = $strategy->getExecutionChain($route, []);

        $this->assertInstanceOf('League\Route\Middleware\ExecutionChain', $chain);

        $request  = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $response->expects($this->at(0))->method('withHeader')->with($this->equalTo('invoke'), $this->equalTo('true'))->will($this->returnSelf());
        $response->expects($this->at(1))->method('withHeader')->with($this->equalTo('action'), $this->equalTo('true'))->will($this->returnSelf());
        $response->expects($this->at(2))->method('withHeader')->with($this->equalTo('controller'), $this->equalTo('true'))->will($this->returnSelf());

        $newResponse = $chain->execute($request, $response);

        $this->assertSame($response, $newResponse);
    }

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
        $route->expects($this->once())->method('getMiddlewareStack')->will($this->returnValue([]));

        $strategy = new ApplicationStrategy;
        $chain    = $strategy->getExecutionChain($route, []);

        $request  = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $chain->execute($request, $response);
    }
}
