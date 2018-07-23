<?php

namespace League\Route\Test\Strategy;

use Exception;
use League\Route\Strategy\JsonStrategy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class JsonStrategyTest extends \PHPUnit_Framework_TestCase
{
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

        $strategy = new JsonStrategy;
        $callable = $strategy->getCallable($route, []);

        $request  = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $next = function ($request, $response) {
            return $response;
        };

        $callable($request, $response, $next);
    }

    /**
     * Test exception decorator will only use the first message line for reason phrase.
     *
     * @return void
     */
    public function testExceptionDecoratorWillOnlyUseTheFirstMessageLineForReasonPhrase()
    {
        $exception = new Exception("some long message\nwith multiple\nlines");

        $strategy = new JsonStrategy;
        $callable = $strategy->getExceptionDecorator($exception);

        $request = $this->prophesize('\Psr\Http\Message\ServerRequestInterface');

        $stream = $this->prophesize('\Psr\Http\Message\StreamInterface');
        $stream->write(json_encode([
            'status_code'   => 500,
            'reason_phrase' => "some long message\nwith multiple\nlines",
        ]))
            ->shouldBeCalled();

        $response = $this->prophesize('\Psr\Http\Message\ResponseInterface');

        $response->getBody()
            ->shouldBeCalled()
            ->willReturn($stream->reveal());

        $response->withAddedHeader('content-type', 'application/json')
            ->shouldBeCalled()
            ->willReturn($response->reveal());

        $response->withStatus(500, 'some long message')
            ->shouldBeCalled()
            ->willReturn($response->reveal());

        $callable($request->reveal(), $response->reveal());
    }
}
