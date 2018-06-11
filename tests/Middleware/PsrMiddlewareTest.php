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
}
