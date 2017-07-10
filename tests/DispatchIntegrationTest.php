<?php

namespace League\Route\Test;

use Exception;
use League\Route\Http\Exception\BadRequestException;
use League\Route\RouteCollection;
use League\Route\Strategy\JsonStrategy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DispatchIntegrationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the collection/dispatcher can dispatch to a found route.
     *
     * @return void
     */
    public function testDispatchesFoundRoute()
    {
        $collection = new RouteCollection;

        $collection->map('GET', '/example/{something}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) {
            $this->assertSame([
                'something' => 'route'
            ], $args);

            return $response;
        });

        $request  = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');
        $uri      = $this->getMock('Psr\Http\Message\UriInterface');

        $uri->expects($this->once())->method('getPath')->will($this->returnValue('/example/route'));

        $request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $request->expects($this->once())->method('getUri')->will($this->returnValue($uri));

        $returnedResponse = $collection->dispatch($request, $response);

        $this->assertSame($response, $returnedResponse);
    }

    /**
     * Asserts that the collection/dispatcher can filter through to exception decorator.
     *
     * @return void
     */
    public function testDispatchesExceptionRoute()
    {
        $this->setExpectedException('Exception');

        $collection = new RouteCollection;

        $collection->map('GET', '/example/route', function () {
            throw new Exception;
        });

        $request  = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');
        $uri      = $this->getMock('Psr\Http\Message\UriInterface');

        $uri->expects($this->once())->method('getPath')->will($this->returnValue('/example/route'));

        $request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $request->expects($this->once())->method('getUri')->will($this->returnValue($uri));

        $collection->dispatch($request, $response);
    }

    /**
     * Asserts that the collection/dispatcher can filter through to exception decorator with the json strategy.
     *
     * @return void
     */
    public function testDispatchesExceptionWithJsonStrategyRoute()
    {
        $collection = (new RouteCollection)->setStrategy(new JsonStrategy);

        $collection->map('GET', '/example/route', function () {
            throw new Exception('Blah');
        });

        $request  = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');
        $uri      = $this->getMock('Psr\Http\Message\UriInterface');
        $body     = $this->getMock('Psr\Http\Message\StreamInterface');

        $uri->expects($this->once())->method('getPath')->will($this->returnValue('/example/route'));

        $request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $request->expects($this->once())->method('getUri')->will($this->returnValue($uri));

        $body->expects($this->once())->method('write')->with($this->equalTo(json_encode([
            'status_code'   => 500,
            'reason_phrase' => 'Blah'
        ])));

        $response->expects($this->once())->method('getBody')->will($this->returnValue($body));
        $response->expects($this->once())->method('withAddedHeader')->with($this->equalTo('content-type'), $this->equalTo('application/json'))->will($this->returnSelf());
        $response->expects($this->once())->method('withStatus')->with($this->equalTo(500), $this->equalTo('Blah'))->will($this->returnSelf());

        $resultResponse = $collection->dispatch($request, $response);

        $this->assertSame($response, $resultResponse);
    }

    /**
     * Asserts that the collection/dispatcher can filter through exception decorator for http exception with the json strategy.
     *
     * @return void
     */
    public function testDispatchesHttpExceptionWithJsonStrategyRoute()
    {
        $collection = (new RouteCollection)->setStrategy(new JsonStrategy);

        $collection->map('GET', '/example/route', function () {
            throw new BadRequestException;
        });

        $request  = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');
        $uri      = $this->getMock('Psr\Http\Message\UriInterface');
        $body     = $this->getMock('Psr\Http\Message\StreamInterface');

        $uri->expects($this->once())->method('getPath')->will($this->returnValue('/example/route'));

        $request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $request->expects($this->once())->method('getUri')->will($this->returnValue($uri));

        $body->expects($this->once())->method('isWritable')->will($this->returnValue(true));
        $body->expects($this->once())->method('write')->with($this->equalTo(json_encode([
            'status_code'   => 400,
            'reason_phrase' => 'Bad Request'
        ])));

        $response->expects($this->exactly(2))->method('getBody')->will($this->returnValue($body));
        $response->expects($this->once())->method('withAddedHeader')->with($this->equalTo('content-type'), $this->equalTo('application/json'))->will($this->returnSelf());
        $response->expects($this->once())->method('withStatus')->with($this->equalTo(400), $this->equalTo('Bad Request'))->will($this->returnSelf());

        $resultResponse = $collection->dispatch($request, $response);

        $this->assertSame($response, $resultResponse);
    }

    /**
     * Asserts that the collection/dispatcher can dispatch to a not found route.
     *
     * @return void
     */
    public function testDispatchesNotFoundRoute()
    {
        $this->setExpectedException('League\Route\Http\Exception\NotFoundException');

        $collection = new RouteCollection;

        $request  = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');
        $uri      = $this->getMock('Psr\Http\Message\UriInterface');

        $uri->expects($this->once())->method('getPath')->will($this->returnValue('/example/route'));

        $request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $request->expects($this->once())->method('getUri')->will($this->returnValue($uri));

        $collection->dispatch($request, $response);
    }

    /**
     * Asserts that the collection/dispatcher can dispatch to a not found route with json strategy.
     *
     * @return void
     */
    public function testDispatchesNotFoundRouteWithJsonStrategy()
    {
        $collection = (new RouteCollection)->setStrategy(new JsonStrategy);

        $request  = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');
        $uri      = $this->getMock('Psr\Http\Message\UriInterface');
        $body     = $this->getMock('Psr\Http\Message\StreamInterface');

        $uri->expects($this->once())->method('getPath')->will($this->returnValue('/example/route'));

        $body->expects($this->once())->method('isWritable')->will($this->returnValue(true));
        $body->expects($this->once())->method('write')->with($this->equalTo(json_encode([
            'status_code'   => 404,
            'reason_phrase' => 'Not Found'
        ])));

        $request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $request->expects($this->once())->method('getUri')->will($this->returnValue($uri));

        $response->expects($this->once())->method('withAddedHeader')->with($this->equalTo('content-type'), $this->equalTo('application/json'))->will($this->returnSelf());
        $response->expects($this->once())->method('withStatus')->with($this->equalTo(404), $this->equalTo('Not Found'))->will($this->returnSelf());
        $response->expects($this->exactly(2))->method('getBody')->will($this->returnValue($body));

        $returnedResponse = $collection->dispatch($request, $response);

        $this->assertSame($response, $returnedResponse);
    }

    /**
     * Asserts that the collection/dispatcher can dispatch to a not allowed route.
     *
     * @return void
     */
    public function testDispatchesNotAllowedRoute()
    {
        $this->setExpectedException('League\Route\Http\Exception\MethodNotAllowedException');

        $collection = new RouteCollection;

        $collection->map('GET', '/example/{something}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) {
            return $response;
        });

        $request  = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');
        $uri      = $this->getMock('Psr\Http\Message\UriInterface');

        $uri->expects($this->once())->method('getPath')->will($this->returnValue('/example/route'));

        $request->expects($this->once())->method('getMethod')->will($this->returnValue('POST'));
        $request->expects($this->once())->method('getUri')->will($this->returnValue($uri));

        $collection->dispatch($request, $response);
    }

    /**
     * Asserts that the collection/dispatcher can dispatch to a not allowed route with json strategy.
     *
     * @return void
     */
    public function testDispatchesNotAllowedRouteWithJsonStrategy()
    {
        $collection = (new RouteCollection)->setStrategy(new JsonStrategy);

        $collection->map('GET', '/example/{something}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) {
            return $response;
        });

        $request  = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');
        $uri      = $this->getMock('Psr\Http\Message\UriInterface');
        $body     = $this->getMock('Psr\Http\Message\StreamInterface');

        $uri->expects($this->once())->method('getPath')->will($this->returnValue('/example/route'));

        $body->expects($this->once())->method('isWritable')->will($this->returnValue(true));
        $body->expects($this->once())->method('write')->with($this->equalTo(json_encode([
            'status_code'   => 405,
            'reason_phrase' => 'Method Not Allowed'
        ])));

        $request->expects($this->once())->method('getMethod')->will($this->returnValue('POST'));
        $request->expects($this->once())->method('getUri')->will($this->returnValue($uri));

        $response->expects($this->at(0))->method('withAddedHeader')->with($this->equalTo('Allow'), $this->equalTo('GET'))->will($this->returnSelf());
        $response->expects($this->at(1))->method('withAddedHeader')->with($this->equalTo('content-type'), $this->equalTo('application/json'))->will($this->returnSelf());
        $response->expects($this->once())->method('withStatus')->with($this->equalTo(405), $this->equalTo('Method Not Allowed'))->will($this->returnSelf());
        $response->expects($this->exactly(2))->method('getBody')->will($this->returnValue($body));

        $returnedResponse = $collection->dispatch($request, $response);

        $this->assertSame($response, $returnedResponse);
    }

    /**
     * Asserts that a route cannot be added after dispatcher has been built
     * @return void
     */
    public function testThatRoutesCannotBeAddedAfterDispatch()
    {
        $collection = new RouteCollection;

        $collection->map('GET', '/something', function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        });

        $request  = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');
        $uri      = $this->getMock('Psr\Http\Message\UriInterface');

        $uri->expects($this->once())->method('getPath')->will($this->returnValue('/something'));

        $request->method('getMethod')->will($this->returnValue('GET'));
        $request->method('getUri')->will($this->returnValue($uri));

        $collection->dispatch($request, $response);
        $this->setExpectedException('Exception', 'Cannot add routes after dispatching a request');
        $collection->map('GET', '/something-else', function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        });
    }
}
