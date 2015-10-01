<?php

namespace League\Route\Test;

use League\Route\RouteCollection;
use League\Route\Strategy\JsonStrategy;

class FunctionalDispatcherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the dispatcher handles a not found route.
     */
    public function testDispatcherHandlesNotFound()
    {
        $this->setExpectedException('League\Route\Http\Exception\NotFoundException');

        $route = new RouteCollection;

        $uri = $this->getMock('Psr\Http\Message\UriInterface');
        $uri->expects($this->once())->method('getPath')->will($this->returnValue('/example'));

        $request = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $request->expects($this->once())->method('getUri')->will($this->returnValue($uri));

        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $route->dispatch($request, $response);
    }

    /**
     * Asserts that the dispatcher handles a not found route with json strategy.
     */
    public function testDispatcherHandlesNotFoundWithJsonStrategy()
    {
        $route = new RouteCollection;
        $route->setStrategy(new JsonStrategy);

        $uri = $this->getMock('Psr\Http\Message\UriInterface');
        $uri->expects($this->once())->method('getPath')->will($this->returnValue('/example'));

        $request = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $request->expects($this->once())->method('getUri')->will($this->returnValue($uri));

        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $body = $this->getMock('Psr\Http\Message\StreamInterface');
        $body->expects($this->once())->method('isWritable')->will($this->returnValue(true));
        $body->expects($this->once())->method('write');

        $response = $this->getMock('Psr\Http\Message\ResponseInterface');
        $response->expects($this->any())->method('withAddedHeader')->will($this->returnSelf());
        $response->expects($this->exactly(2))->method('getBody')->will($this->returnValue($body));
        $response->expects($this->once())->method('withStatus')->will($this->returnSelf());

        $response = $route->dispatch($request, $response);

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
    }

    /**
     * Asserts that the dispatcher handles a not found route.
     */
    public function testDispatcherHandlesNotAllowed()
    {
        $this->setExpectedException('League\Route\Http\Exception\MethodNotAllowedException');

        $route = new RouteCollection;

        $uri = $this->getMock('Psr\Http\Message\UriInterface');
        $uri->expects($this->once())->method('getPath')->will($this->returnValue('/example'));

        $request = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $request->expects($this->once())->method('getUri')->will($this->returnValue($uri));

        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $route->post('/example', function () {});

        $route->dispatch($request, $response);
    }

    /**
     * Asserts that the dispatcher handles a not found route with json strategy.
     */
    public function testDispatcherHandlesNotAllowedWithJsonStrategy()
    {
        $route = new RouteCollection;
        $route->setStrategy(new JsonStrategy);

        $uri = $this->getMock('Psr\Http\Message\UriInterface');
        $uri->expects($this->once())->method('getPath')->will($this->returnValue('/example'));

        $request = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $request->expects($this->once())->method('getUri')->will($this->returnValue($uri));

        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $body = $this->getMock('Psr\Http\Message\StreamInterface');
        $body->expects($this->once())->method('isWritable')->will($this->returnValue(true));
        $body->expects($this->once())->method('write');

        $response = $this->getMock('Psr\Http\Message\ResponseInterface');
        $response->expects($this->any())->method('withAddedHeader')->will($this->returnSelf());
        $response->expects($this->exactly(2))->method('getBody')->will($this->returnValue($body));
        $response->expects($this->once())->method('withStatus')->will($this->returnSelf());

        $route->post('/example', function () {});

        $response = $route->dispatch($request, $response);

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
    }

    /**
     * Asserts that the dispatcher can handle a found route.
     */
    public function testDispatcherHandlesFoundRoute()
    {
        $route = new RouteCollection;

        $uri = $this->getMock('Psr\Http\Message\UriInterface');
        $uri->expects($this->once())->method('getPath')->will($this->returnValue('/example'));

        $request = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $request->expects($this->once())->method('getUri')->will($this->returnValue($uri));

        $route->get('/example', function ($request, $response) {
            return $response;
        });

        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $newResponse = $route->dispatch($request, $response);

        $this->assertSame($response, $newResponse);
    }
}
