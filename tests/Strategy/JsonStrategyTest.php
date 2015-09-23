<?php

namespace League\Route\Test\Strategy;

use League\Route\Strategy\JsonStrategy;

class JsonStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that an exception is thrown when a response cannot b built.
     */
    public function testThrowsExceptionWhenAJsonResponseCannotBeBuilt()
    {
        $this->setExpectedException('RuntimeException');

        $originalReq = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $originalRes = $this->getMock('Psr\Http\Message\ResponseInterface');

        $strategy = (new JsonStrategy)->setRequest($originalReq)->setResponse($originalRes);

        $response = $strategy->dispatch(function ($request, $vars) use ($originalReq) {
            $this->assertSame($request, $originalReq);

            return 'Hello, World!';
        }, []);
    }

    /**
     * Asserts that a json response is built when a http exception is thrown.
     */
    public function testBuildsJsonResponseFromHttpException()
    {
        $originalReq = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $originalRes = $this->getMock('Psr\Http\Message\ResponseInterface');
        $exception   = $this->getMockBuilder('League\Route\Http\Exception', ['buildJsonResponse'])->disableOriginalConstructor()->getMock();

        $exception->expects($this->once())->method('buildJsonResponse')->with($this->equalTo($originalRes))->will($this->returnValue($originalRes));

        $strategy = (new JsonStrategy)->setRequest($originalReq)->setResponse($originalRes);

        $response = $strategy->dispatch(function ($request, $vars) use ($originalReq, $exception) {
            $this->assertSame($request, $originalReq);

            throw $exception;
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

        $expected = ['Hello' => 'World!'];

        $originalRes->expects($this->any())->method('getBody')->will($this->returnValue($body));
        $originalRes->expects($this->once())->method('withAddedHeader')->with($this->equalTo('content-type'), $this->equalTo('application/json'))->will($this->returnSelf());

        $body->expects($this->once())->method('isWritable')->will($this->returnValue(true));
        $body->expects($this->once())->method('write')->with($this->equalTo(json_encode($expected)));

        $strategy = (new JsonStrategy)->setRequest($originalReq)->setResponse($originalRes);

        $response = $strategy->dispatch(function ($request, $vars) use ($originalReq, $expected) {
            $this->assertSame($request, $originalReq);

            return $expected;
        }, []);

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
    }
}
