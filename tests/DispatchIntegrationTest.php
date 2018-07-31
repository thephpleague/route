<?php declare(strict_types=1);

namespace League\Route;

use Exception;
use League\Route\Http\Exception\{BadRequestException, MethodNotAllowedException, NotFoundException};
use League\Route\Router;
use League\Route\Strategy\JsonStrategy;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface, StreamInterface, UriInterface};

class DispatchIntegrationTest extends TestCase
{
    /**
     * Asserts that the collection/dispatcher can dispatch to a found route.
     *
     * @return void
     */
    public function testDispatchesFoundRoute() : void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue('/example/route'))
        ;

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('GET'))
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->will($this->returnValue($uri))
        ;

        $router = new Router;

        $router->map('GET', '/example/{something}', function (
            ServerRequestInterface $request,
            array $args
        ) use (
            $response
        ) : ResponseInterface {
            $this->assertSame([
                'something' => 'route'
            ], $args);

            return $response;
        });

        $returnedResponse = $router->dispatch($request);

        $this->assertSame($response, $returnedResponse);
    }

    /**
     * Asserts that the collection/dispatcher can filter through to exception decorator.
     *
     * @return void
     */
    public function testDispatchesExceptionRoute() : void
    {
        $this->expectException(Exception::class);

        $router = new Router;

        $router->map('GET', '/example/route', function () {
            throw new Exception;
        });

        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue('/example/route'))
        ;

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('GET'))
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->will($this->returnValue($uri))
        ;

        $router->dispatch($request, $response);
    }

    /**
     * Asserts that the collection/dispatcher can filter through to exception decorator with the json strategy.
     *
     * @return void
     */
    public function testDispatchesExceptionWithJsonStrategyRoute() : void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);
        $body     = $this->createMock(StreamInterface::class);

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue('/example/route'))
        ;

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('GET'))
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->will($this->returnValue($uri))
        ;

        $body
            ->expects($this->once())
            ->method('write')
            ->with($this->equalTo(json_encode([
                'status_code'   => 500,
                'reason_phrase' => 'Blah'
            ])))
        ;

        $response
            ->expects($this->once())
            ->method('getBody')
            ->will($this->returnValue($body))
        ;

        $response
            ->expects($this->once())
            ->method('withAddedHeader')
            ->with($this->equalTo('content-type'), $this->equalTo('application/json'))
            ->will($this->returnSelf())
        ;

        $response
            ->expects($this->once())
            ->method('withStatus')
            ->with($this->equalTo(500), $this->equalTo('Blah'))
            ->will($this->returnSelf())
        ;

        $router = (new Router)->setStrategy(new JsonStrategy(function () use ($response) {
            return $response;
        }));

        $router->map('GET', '/example/route', function () {
            throw new Exception('Blah');
        });

        $resultResponse = $router->dispatch($request, $response);

        $this->assertSame($response, $resultResponse);
    }

    /**
     * Asserts that the collection/dispatcher can filter through exception decorator
     * for http exception with the json strategy.
     *
     * @return void
     */
    public function testDispatchesHttpExceptionWithJsonStrategyRoute() : void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);
        $body     = $this->createMock(StreamInterface::class);

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue('/example/route'))
        ;

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('GET'))
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->will($this->returnValue($uri))
        ;

        $body
            ->expects($this->once())
            ->method('isWritable')
            ->will($this->returnValue(true))
        ;

        $body
            ->expects($this->once())
            ->method('write')
            ->with($this->equalTo(json_encode([
                'status_code'   => 400,
                'reason_phrase' => 'Bad Request'
            ])))
        ;

        $response
            ->expects($this->exactly(2))
            ->method('getBody')
            ->will($this->returnValue($body))
        ;

        $response
            ->expects($this->once())
            ->method('withAddedHeader')
            ->with($this->equalTo('content-type'), $this->equalTo('application/json'))
            ->will($this->returnSelf())
        ;

        $response
            ->expects($this->once())
            ->method('withStatus')
            ->with($this->equalTo(400), $this->equalTo('Bad Request'))
            ->will($this->returnSelf())
        ;

        $router = (new Router)->setStrategy(new JsonStrategy(function () use ($response) {
            return $response;
        }));

        $router->map('GET', '/example/route', function () {
            throw new BadRequestException;
        });

        $resultResponse = $router->dispatch($request, $response);

        $this->assertSame($response, $resultResponse);
    }

    /**
     * Asserts that the collection/dispatcher can dispatch to a not found route.
     *
     * @return void
     */
    public function testDispatchesNotFoundRoute() : void
    {
        $this->expectException(NotFoundException::class);

        $router = new Router;

        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue('/example/route'))
        ;

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('GET'))
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->will($this->returnValue($uri))
        ;

        $router->dispatch($request, $response);
    }

    /**
     * Asserts that the collection/dispatcher can dispatch to a not found route with json strategy.
     *
     * @return void
     */
    public function testDispatchesNotFoundRouteWithJsonStrategy() : void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);
        $body     = $this->createMock(StreamInterface::class);

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue('/example/route'))
        ;

        $body
            ->expects($this->once())
            ->method('isWritable')
            ->will($this->returnValue(true))
        ;

        $body
            ->expects($this->once())
            ->method('write')
            ->with($this->equalTo(json_encode([
                'status_code'   => 404,
                'reason_phrase' => 'Not Found'
            ])))
        ;

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('GET'))
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->will($this->returnValue($uri))
        ;

        $response
            ->expects($this->once())
            ->method('withAddedHeader')
            ->with($this->equalTo('content-type'), $this->equalTo('application/json'))
            ->will($this->returnSelf())
        ;

        $response
            ->expects($this->once())
            ->method('withStatus')
            ->with($this->equalTo(404), $this->equalTo('Not Found'))
            ->will($this->returnSelf())
        ;

        $response
            ->expects($this->exactly(2))
            ->method('getBody')
            ->will($this->returnValue($body))
        ;

        $router = (new Router)->setStrategy(new JsonStrategy(function () use ($response) {
            return $response;
        }));

        $returnedResponse = $router->dispatch($request, $response);

        $this->assertSame($response, $returnedResponse);
    }

    /**
     * Asserts that the collection/dispatcher can dispatch to a not allowed route.
     *
     * @return void
     */
    public function testDispatchesNotAllowedRoute() : void
    {
        $this->expectException(MethodNotAllowedException::class);

        $router = new Router;

        $router->map('GET', '/example/{something}', function (ServerRequestInterface $request, array $args) {
            return $response;
        });

        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue('/example/route'))
        ;

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('POST'))
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->will($this->returnValue($uri))
        ;

        $router->dispatch($request, $response);
    }

    /**
     * Asserts that the collection/dispatcher can dispatch to a not allowed route with json strategy.
     *
     * @return void
     */
    public function testDispatchesNotAllowedRouteWithJsonStrategy() : void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);
        $body     = $this->createMock(StreamInterface::class);

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue('/example/route'))
        ;

        $body
            ->expects($this->once())
            ->method('isWritable')
            ->will($this->returnValue(true))
        ;

        $body
            ->expects($this->once())
            ->method('write')
            ->with($this->equalTo(json_encode([
                'status_code'   => 405,
                'reason_phrase' => 'Method Not Allowed'
            ])))
        ;

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('POST'))
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->will($this->returnValue($uri))
        ;

        $response
            ->expects($this->at(0))
            ->method('withAddedHeader')
            ->with($this->equalTo('Allow'), $this->equalTo('GET'))
            ->will($this->returnSelf())
        ;

        $response
            ->expects($this->at(1))
            ->method('withAddedHeader')
            ->with($this->equalTo('content-type'), $this->equalTo('application/json'))
            ->will($this->returnSelf())
        ;

        $response
            ->expects($this->once())
            ->method('withStatus')
            ->with($this->equalTo(405), $this->equalTo('Method Not Allowed'))
            ->will($this->returnSelf())
        ;

        $response
            ->expects($this->exactly(2))
            ->method('getBody')
            ->will($this->returnValue($body))
        ;

        $router = (new Router)->setStrategy(new JsonStrategy(function () use ($response) {
            return $response;
        }));

        $router->map('GET', '/example/{something}', function (ServerRequestInterface $request, array $args) {
            return $response;
        });

        $returnedResponse = $router->dispatch($request, $response);

        $this->assertSame($response, $returnedResponse);
    }
}
