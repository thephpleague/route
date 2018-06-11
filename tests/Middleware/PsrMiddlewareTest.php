<?php

namespace Middleware;

use League\Route\Middleware\ExecutionChain;
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

        $chain->execute($request, $response);

        $this->assertEquals(1, $middleware->getCalls());
    }
}
