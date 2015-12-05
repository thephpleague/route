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

        $strategy = (new RequestResponseStrategy)->setRequest($originalReq)->setResponse($originalRes);

        $response = $strategy->dispatch(function ($request, $response, $vars) use ($originalReq, $originalRes) {
            $this->assertSame($request, $originalReq);
            $this->assertSame($response, $originalRes);

            return $response;
        }, []);

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
    }

    /**
     * Asserts that the dispatch method invokes the callable and builds a response from the return.
     */
    public function testDispatchInvokesCallableAndBuildsResponse()
    {
        $originalReq = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $originalRes = $this->getMock('Psr\Http\Message\ResponseInterface');

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
        }, []);

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
        }, []);
    }

    /**
     * Asserts that strategy attempts to fetch request from container when it hasn't been set before.
     */
    public function testDispatchFetchesResponseFromContainer()
    {
        $request = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

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
        }, []);

        $this->assertTrue($isSameRequest);
    }
}
