<?php

namespace League\Route\Test\Strategy;

use League\Route\Strategy\ParamStrategy;

class ParamStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that an exception is thrown when attempting to use strategy
     * with an incompatible container.
     */
    public function testThrowsWhenContainerNotCompatible()
    {
        $this->setExpectedException('RuntimeException');

        $container = $this->getMock('Interop\Container\ContainerInterface');

        $strategy = (new ParamStrategy)->setContainer($container);

        $strategy->dispatch(function () {}, []);
    }

    /**
     * Asserts that the dispatch method invokes the callable and builds a response from the return.
     */
    public function testDispatchInvokesCallableAndBuildsResponse()
    {
        $originalReq = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $originalRes = $this->getMock('Psr\Http\Message\ResponseInterface');
        $container   = $this->getMock('League\Container\ContainerInterface');

        $body = $this->getMock('Psr\Http\Message\StreamInterface');

        $expected = 'Hello, World!';
        $callback = function () {};

        $container->expects($this->once())->method('call')->with($this->equalTo($callback), $this->equalTo([]))->will($this->returnValue($expected));

        $originalRes->expects($this->any())->method('getBody')->will($this->returnValue($body));

        $body->expects($this->once())->method('isWritable')->will($this->returnValue(true));
        $body->expects($this->once())->method('write')->with($this->equalTo($expected));

        $strategy = (new ParamStrategy)->setRequest($originalReq)->setResponse($originalRes)->setContainer($container);

        $response = $strategy->dispatch($callback, []);

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
    }
}
