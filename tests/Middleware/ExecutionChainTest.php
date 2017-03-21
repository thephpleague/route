<?php

namespace League\Route\Test\Middleware;

use League\Route\Middleware\ExecutionChain;
use League\Route\Test\Asset\Controller;

class ExecutionChainTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the execution chain can set and get middleware.
     *
     * @return void
     */
    public function testExecutionChainCanSetAndGetMiddleware()
    {
        $chain = new ExecutionChain;
        $middleware = new Controller;

        $chain->middleware($middleware)->middleware($middleware);

        $this->assertSame([
            $middleware, $middleware
        ], $chain->getMiddlewareStack());
    }

    /**
     * Asserts that the execution chain can set a middleware collection.
     *
     * @return void
     */
    public function testExecutionChainCanSetMiddlewareCollection()
    {
        $chain = new ExecutionChain;
        $middleware = new Controller;

        $chain->middlewares($middleware, $middleware);
        $chain->middlewares([$middleware, $middleware]);
        $chain->middlewares([$middleware, $middleware], $middleware);

        $this->assertSame([
            $middleware, $middleware, $middleware, $middleware,
            $middleware, $middleware, $middleware
        ], $chain->getMiddlewareStack());
    }

    /**
     * Asserts that the execution chain can build and execute a chain.
     *
     * @return void
     */
    public function testExecutionChainBuildsAndExecutesChain()
    {
        $request  = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $response->expects($this->at(0))->method('withHeader')->with($this->equalTo('invoke'), $this->equalTo('true'))->will($this->returnSelf());
        $response->expects($this->at(1))->method('withHeader')->with($this->equalTo('action'), $this->equalTo('true'))->will($this->returnSelf());

        $chain = new ExecutionChain;
        $chain->middleware(new Controller);
        $chain->middleware([new Controller, 'action']);

        $this->assertSame($response, $chain->execute($request, $response));
    }
}
