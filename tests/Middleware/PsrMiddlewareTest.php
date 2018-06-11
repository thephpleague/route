<?php

namespace Middleware;

use League\Route\Middleware\ExecutionChain;
use League\Route\Test\Asset\Controller;
use League\Route\Test\Asset\PSR15AfterMiddleware;
use League\Route\Test\Asset\PSR15Middleware;

class PsrMiddlewareTest extends \PHPUnit_Framework_TestCase
{

    public function testItCanAddAPsrMiddleware()
    {
        $chain = new ExecutionChain();
        $middleware = new PSR15Middleware();

        $chain->middleware($middleware);

        $this->assertSame([$middleware], $chain->getMiddlewareStack());
    }

    public function testItCanProcessAPSR15Middleware()
    {
        $chain = new ExecutionChain();
        $middleware = new PSR15Middleware();
        $request  = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $chain->middleware($middleware);
        $executedResponse = $chain->execute($request, $response);

        $this->assertSame($response, $executedResponse);
        $this->assertEquals(1, $middleware->getCalls());
    }

    public function testItCanProcessAPSR15MiddlewareMultipleTimes()
    {
        $chain = new ExecutionChain();
        $middleware = new PSR15Middleware();
        $request  = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $chain->middleware($middleware);
        $chain->middleware($middleware);
        $executedResponse = $chain->execute($request, $response);

        $this->assertSame($response, $executedResponse);
        $this->assertEquals(2, $middleware->getCalls());
    }

    public function testItCanProcessPSR15andNonPSR15Middlewares()
    {
        $chain = new ExecutionChain();
        $psr15Middleware = new PSR15Middleware();
        $middleware = new Controller();
        $request  = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $response->expects($this->at(0))->method('withHeader')->with($this->equalTo('invoke'), $this->equalTo('true'))->will($this->returnSelf());
        $response->expects($this->at(1))->method('withHeader')->with($this->equalTo('action'), $this->equalTo('true'))->will($this->returnSelf());

        $chain->middleware($psr15Middleware);
        $chain->middleware([$middleware, 'action']);
        $chain->middleware($middleware);
        $calls = 0;
        $chain->middleware(function ($request, $response, $next) use (&$calls) {
            $calls++;
            return $next($request, $response);
        });

        $executedResponse = $chain->execute($request, $response);

        $this->assertSame($response, $executedResponse);
        $this->assertEquals(1, $psr15Middleware->getCalls());
        $this->assertEquals(1, $calls);
    }

    public function testPSR15MiddlewareAffectsTheResponse()
    {
        $chain = new ExecutionChain();
        $psr15Middleware = new PSR15AfterMiddleware();
        $request  = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $response->expects($this->at(0))->method('withHeader')->with($this->equalTo('psr15'), $this->equalTo('true'))->will($this->returnSelf());

        $chain->middleware($psr15Middleware);

        $executedResponse = $chain->execute($request, $response);

        $this->assertSame($response, $executedResponse);
        $this->assertEquals(1, $psr15Middleware->getCalls());
    }
}
