<?php

namespace League\Route\Test\Strategy;

use League\Route\Strategy\RequestResponseStrategy;

class RequestResponseStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the dispatch method invokes the callable and returns a response.
     */
    public function testDispatchInvokesCallableAndReturnsResponse()
    {
        $originalReq = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $originalRes = $this->getMock('Psr\Http\Message\ResponseInterface');
        $route       = $this->getMock('League\Route\Route');
        $runner      = $this->getMock('League\Route\Middleware\Runner');

        $runner->expects($this->once())->method('run')->will($this->returnValue($originalRes));
        $route->expects($this->once())->method('getMiddlewareRunner')->will($this->returnValue($runner));

        $strategy = (new RequestResponseStrategy)->setRequest($originalReq)->setResponse($originalRes);

        $response = $strategy->dispatch(function ($request, $response, $vars) use ($originalReq, $originalRes) {
            $this->assertSame($request, $originalReq);
            $this->assertSame($response, $originalRes);

            return $response;
        }, [], $route);

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
    }

    /**
     * Asserts that the dispatch method invokes the callable and builds a response from the return.
     */
    public function testDispatchInvokesCallableAndBuildsResponse()
    {
        $originalReq = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $originalRes = $this->getMock('Psr\Http\Message\ResponseInterface');

        $route = new \League\Route\Route;

        $body = $this->getMock('Psr\Http\Message\StreamInterface');

        $expected = 'Hello, World!';

        $originalRes->expects($this->any())->method('getBody')->will($this->returnValue($body));
        $body->expects($this->once())->method('isWritable')->will($this->returnValue(true));
        $body->expects($this->once())->method('write')->with($this->equalTo($expected));

        $strategy = (new RequestResponseStrategy)->setRequest($originalReq)->setResponse($originalRes);

        $response = $strategy->dispatch(function ($request, $response, $vars) use ($originalReq, $originalRes, $expected) {
            $this->assertSame($request, $originalReq);
            $this->assertSame($response, $originalRes);

            return $expected;
        }, [], $route);

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
    }

    /**
     * Asserts that the dispatch method invokes the callable but throws an exception when a response cannot be built.
     */
    public function testDispatchInvokesCallableButThrowsExceptionWhenCannotBuildResponse()
    {
        $this->setExpectedException('RuntimeException');

        $originalReq = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $originalRes = $this->getMock('Psr\Http\Message\ResponseInterface');
        $route       = $this->getMock('League\Route\Route');
        $runner      = $this->getMock('League\Route\Middleware\Runner');

        $route = new \League\Route\Route;

        $body = $this->getMock('Psr\Http\Message\StreamInterface');

        $expected = new \stdClass;

        $originalRes->expects($this->any())->method('getBody')->will($this->returnValue($body));
        $body->expects($this->once())->method('isWritable')->will($this->returnValue(true));
        $body->expects($this->once())->method('write')->with($this->equalTo($expected))->will($this->throwException(new \RuntimeException));

        $strategy = (new RequestResponseStrategy)->setRequest($originalReq)->setResponse($originalRes);

        $response = $strategy->dispatch(function ($request, $response, $vars) use ($originalReq, $originalRes, $expected) {
            $this->assertSame($request, $originalReq);
            $this->assertSame($response, $originalRes);

            return $expected;
        }, [], $route);
    }

    /**
     * Asserts that strategy attempts to fetch request from container when it hasn't been set before.
     */
    public function testDispatchFetchesRequestFromContainer()
    {
        $request = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $route  = $this->getMock('League\Route\Route');
        $runner = $this->getMock('League\Route\Middleware\Runner');

        $route = new \League\Route\Route;

        $container = $this->getMock('Interop\Container\ContainerInterface');

        $container
            ->expects($this->any())
            ->method('has')
            ->with('Psr\Http\Message\ServerRequestInterface')
            ->willReturn(true)
        ;

        $container
            ->expects($this->any())
            ->method('get')
            ->with('Psr\Http\Message\ServerRequestInterface')
            ->willReturn($request)
        ;

        $isSameRequest = false;

        $strategy = new RequestResponseStrategy();

        $strategy->setResponse($response);
        $strategy->setContainer($container);

        $strategy->dispatch(function ($actualRequest, $actualResponse) use ($request, &$isSameRequest) {
            if ($actualRequest === $request) {
                $isSameRequest = true;
            }

            return $actualResponse;
        }, [], $route);

        $this->assertTrue($isSameRequest);
    }

    /**
     * Asserts that strategy attempts to fetch response from container when it hasn't been set before.
     */
    public function testDispatchFetchesResponseFromContainer()
    {
        $request = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $container = $this->getMock('Interop\Container\ContainerInterface');

        $route = new \League\Route\Route;

        $container
            ->expects($this->any())
            ->method('has')
            ->with('Psr\Http\Message\ResponseInterface')
            ->willReturn(true)
        ;

        $container
            ->expects($this->any())
            ->method('get')
            ->with('Psr\Http\Message\ResponseInterface')
            ->willReturn($response)
        ;

        $isSameResponse = false;

        $strategy = new RequestResponseStrategy();

        $strategy->setRequest($request);
        $strategy->setContainer($container);

        $strategy->dispatch(function ($actualRequest, $actualResponse) use ($response, &$isSameResponse) {
            if ($actualResponse === $response) {
                $isSameResponse = true;
            }

            return $actualResponse;
        }, [], $route);

        $this->assertTrue($isSameResponse);
    }
}
