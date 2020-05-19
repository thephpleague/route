<?php

declare(strict_types=1);

namespace League\Route\Strategy;

use Exception;
use League\Route\Http\Exception as HttpException;
use League\Route\Http\Exception\{MethodNotAllowedException, NotFoundException};
use League\Route\Route;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface, StreamInterface};
use Psr\Http\Server\RequestHandlerInterface;
use stdClass;

class JsonStrategyTest extends TestCase
{
    public function testStrategyInvokesRouteCallable(): void
    {
        $route = $this->createMock(Route::class);

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $expectedRequest  = $this->createMock(ServerRequestInterface::class);
        $expectedVars     = ['something', 'else'];

        $route
            ->expects($this->once())
            ->method('getCallable')
            ->willReturn(function (
                ServerRequestInterface $request,
                array $vars = []
            ) use (
                $expectedRequest,
                $expectedResponse,
                $expectedVars
            ): ResponseInterface {
                $this->assertSame($expectedRequest, $request);
                $this->assertSame($expectedVars, $vars);
                return $expectedResponse;
            })
        ;

        $route
            ->expects($this->once())
            ->method('getVars')
            ->willReturn($expectedVars)
        ;

        $expectedResponse
            ->expects($this->once())
            ->method('hasHeader')
            ->with($this->equalTo('content-type'))
            ->willReturn(false)
        ;

        $expectedResponse
            ->expects($this->once())
            ->method('withHeader')
            ->with($this->equalTo('content-type'), $this->equalTo('application/json'))
            ->will($this->returnSelf())
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $strategy = new JsonStrategy($factory);
        $response = $strategy->invokeRouteCallable($route, $expectedRequest);
        $this->assertSame($expectedResponse, $response);
    }

    public function testStrategyInvokesRouteCallableWithArrayReturn(): void
    {
        $route = $this->createMock(Route::class);

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $expectedRequest  = $this->createMock(ServerRequestInterface::class);
        $body             = $this->createMock(StreamInterface::class);
        $expectedVars     = ['something', 'else'];

        $expectedResponse
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($body)
        ;

        $expectedResponse
            ->expects($this->once())
            ->method('withHeader')
            ->with($this->equalTo('content-type'), $this->equalTo('application/json'))
            ->will($this->returnSelf())
        ;

        $expectedResponse
            ->expects($this->once())
            ->method('hasHeader')
            ->with($this->equalTo('content-type'))
            ->willReturn(false)
        ;

        $body
            ->expects($this->once())
            ->method('write')
            ->with($this->equalTo(json_encode([$expectedVars[0] => $expectedVars[1]])))
        ;

        $route
            ->expects($this->once())
            ->method('getCallable')
            ->willReturn(function (
                ServerRequestInterface $request,
                array $vars = []
            ) use (
                $expectedRequest,
                $expectedVars
            ): array {
                $this->assertSame($expectedRequest, $request);
                $this->assertSame($expectedVars, $vars);
                return [$vars[0] => $vars[1]];
            })
        ;

        $route
            ->expects($this->once())
            ->method('getVars')
            ->willReturn($expectedVars)
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->willReturn($expectedResponse)
        ;

        $strategy = new JsonStrategy($factory);
        $response = $strategy->invokeRouteCallable($route, $expectedRequest);
        $this->assertSame($expectedResponse, $response);
    }

    /**
     * Asserts that the strategy returns the correct middleware to decorate NotFoundException.
     *
     * @return void
     */
    public function testStrategyReturnsCorrectNotFoundDecorator(): void
    {
        $exception      = $this->createMock(NotFoundException::class);
        $request        = $this->createMock(ServerRequestInterface::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $response       = $this->createMock(ResponseInterface::class);

        $exception
            ->expects($this->once())
            ->method('buildJsonResponse')
            ->with($this->equalTo($response))
            ->willReturn($response)
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->willReturn($response)
        ;

        $strategy = new JsonStrategy($factory);

        $handler = $strategy->getNotFoundDecorator($exception);
        $actualResponse = $handler->process($request, $requestHandler);
        $this->assertSame($response, $actualResponse);
    }

    public function testStrategyReturnsCorrectMethodNotAllowedDecorator(): void
    {
        $exception      = $this->createMock(MethodNotAllowedException::class);
        $request        = $this->createMock(ServerRequestInterface::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $response       = $this->createMock(ResponseInterface::class);

        $exception
            ->expects($this->once())
            ->method('buildJsonResponse')
            ->with($this->equalTo($response))
            ->willReturn($response)
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->willReturn($response)
        ;

        $strategy = new JsonStrategy($factory);
        $handler = $strategy->getMethodNotAllowedDecorator($exception);
        $actualResponse = $handler->process($request, $requestHandler);
        $this->assertSame($response, $actualResponse);
    }

    public function testStrategyReturnsCorrectThrowableHandler(): void
    {
        $request        = $this->createMock(ServerRequestInterface::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $response       = $this->createMock(ResponseInterface::class);
        $body           = $this->createMock(StreamInterface::class);

        $requestHandler
            ->expects($this->once())
            ->method('handle')
            ->with($this->equalTo($request))
            ->will($this->throwException(new Exception('Exception thrown')))
        ;

        $response
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($body)
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
            ->with($this->equalTo(500), $this->equalTo('Exception thrown'))
            ->will($this->returnSelf())
        ;

        $body
            ->expects($this->once())
            ->method('write')
            ->with($this->equalTo(json_encode([
                'status_code'   => 500,
                'reason_phrase' => 'Exception thrown'
            ])))
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->willReturn($response)
        ;

        $strategy = new JsonStrategy($factory);
        $handler = $strategy->getThrowableHandler();
        $actualResponse = $handler->process($request, $requestHandler);
        $this->assertSame($response, $actualResponse);
    }

    public function testStrategyReturnsCorrectHttpExceptionHandler(): void
    {
        $exception      = $this->createMock(HttpException::class);
        $request        = $this->createMock(ServerRequestInterface::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $response       = $this->createMock(ResponseInterface::class);

        $exception
            ->expects($this->once())
            ->method('buildJsonResponse')
            ->with($this->equalTo($response))
            ->willReturn($response)
        ;

        $requestHandler
            ->expects($this->once())
            ->method('handle')
            ->with($this->equalTo($request))
            ->will($this->throwException($exception))
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->willReturn($response)
        ;

        $strategy = new JsonStrategy($factory);
        $handler = $strategy->getThrowableHandler();
        $actualResponse = $handler->process($request, $requestHandler);
        $this->assertSame($response, $actualResponse);
    }

    public function testStrategyInvokesRouteCallableWithObjectReturn(): void
    {
        $route = $this->createMock(Route::class);

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $expectedRequest  = $this->createMock(ServerRequestInterface::class);
        $body             = $this->createMock(StreamInterface::class);
        $expectedVars     = ['something', 'else'];
        $expectedObject = new stdClass();

        $expectedResponse
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($body)
        ;

        $expectedResponse
            ->expects($this->once())
            ->method('withHeader')
            ->with($this->equalTo('content-type'), $this->equalTo('application/json'))
            ->will($this->returnSelf())
        ;

        $expectedResponse
            ->expects($this->once())
            ->method('hasHeader')
            ->with($this->equalTo('content-type'))
            ->willReturn(false)
        ;

        $expectedObject->something = 'else';

        $body
            ->expects($this->once())
            ->method('write')
            ->with($this->equalTo(json_encode([$expectedVars[0] => $expectedVars[1]])))
        ;

        $route
            ->expects($this->once())
            ->method('getCallable')
            ->willReturn(function (
                ServerRequestInterface $request
            ) use (
                $expectedRequest,
                $expectedObject
            ): stdClass {
                $this->assertSame($expectedRequest, $request);
                return $expectedObject;
            })
        ;

        $route
            ->expects($this->once())
            ->method('getVars')
            ->willReturn($expectedVars)
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->willReturn($expectedResponse)
        ;

        $strategy = new JsonStrategy($factory);
        $response = $strategy->invokeRouteCallable($route, $expectedRequest);
        $this->assertSame($expectedResponse, $response);
    }

    public function testStrategyProvidesOptionsRouteCallable(): void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $factory  = $this->createMock(ResponseFactoryInterface::class);

        $response
            ->expects($this->at(0))
            ->method('withHeader')
            ->with($this->equalTo('allow'), $this->equalTo('GET, POST'))
            ->will($this->returnSelf())
        ;

        $response
            ->expects($this->at(1))
            ->method('withHeader')
            ->with($this->equalTo('access-control-allow-methods'), $this->equalTo('GET, POST'))
            ->will($this->returnSelf())
        ;

        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->willReturn($response)
        ;

        $strategy = new JsonStrategy($factory);
        $callable = $strategy->getOptionsCallable(['GET', 'POST']);

        $callable($request);
    }
}
