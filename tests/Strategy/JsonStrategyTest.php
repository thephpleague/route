<?php declare(strict_types=1);

namespace League\Route\Strategy;

use Exception;
use League\Route\Http\Exception as HttpException;
use League\Route\Http\Exception\{MethodNotAllowedException, NotFoundException};
use League\Route\Route;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface, StreamInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class JsonStrategyTest extends TestCase
{
    /**
     * Asserts that the strategy properly invokes the route callable.
     *
     * @return void
     */
    public function testStrategyInvokesRouteCallable() : void
    {
        $route = $this->createMock(Route::class);

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $expectedRequest  = $this->createMock(ServerRequestInterface::class);
        $expectedVars     = ['something', 'else'];

        $route
            ->expects($this->once())
            ->method('getCallable')
            ->will($this->returnValue(
                function (
                    ServerRequestInterface $request,
                    array                  $vars = []
                ) use (
                    $expectedRequest,
                    $expectedResponse,
                    $expectedVars
                ) : ResponseInterface {
                    $this->assertSame($expectedRequest, $request);
                    $this->assertSame($expectedVars, $vars);
                    return $expectedResponse;
                }
            ))
        ;

        $route
            ->expects($this->once())
            ->method('getVars')
            ->will($this->returnValue($expectedVars))
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $strategy = new JsonStrategy($factory);
        $response = $strategy->invokeRouteCallable($route, $expectedRequest);

        $this->assertSame($expectedResponse, $response);
    }

    /**
     * Asserts that the strategy properly invokes the route callable with an array return.
     *
     * @return void
     */
    public function testStrategyInvokesRouteCallableWithArrayReturn() : void
    {
        $route = $this->createMock(Route::class);

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $expectedRequest  = $this->createMock(ServerRequestInterface::class);
        $body             = $this->createMock(StreamInterface::class);
        $expectedVars     = ['something', 'else'];

        $expectedResponse
            ->expects($this->once())
            ->method('getBody')
            ->will($this->returnValue($body))
        ;

        $expectedResponse
            ->expects($this->once())
            ->method('withAddedHeader')
            ->with($this->equalTo('content-type'), $this->equalTo('application/json'))
            ->will($this->returnSelf())
        ;

        $expectedResponse
            ->expects($this->once())
            ->method('withStatus')
            ->with($this->equalTo(200))
            ->will($this->returnSelf())
        ;

        $expectedResponse
            ->expects($this->once())
            ->method('hasHeader')
            ->with($this->equalTo('content-type'))
            ->will($this->returnValue(false))
        ;

        $body
            ->expects($this->once())
            ->method('write')
            ->with($this->equalTo(json_encode([$expectedVars[0] => $expectedVars[1]])))
        ;

        $route
            ->expects($this->once())
            ->method('getCallable')
            ->will($this->returnValue(
                function (
                    ServerRequestInterface $request,
                    array                  $vars = []
                ) use (
                    $expectedRequest,
                    $expectedVars
                ) : array {
                    $this->assertSame($expectedRequest, $request);
                    $this->assertSame($expectedVars, $vars);
                    return [$vars[0] => $vars[1]];
                }
            ))
        ;

        $route
            ->expects($this->once())
            ->method('getVars')
            ->will($this->returnValue($expectedVars))
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->will($this->returnValue($expectedResponse))
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
    public function testStrategyReturnsCorrectNotFoundDecorator() : void
    {
        $exception      = $this->createMock(NotFoundException::class);
        $request        = $this->createMock(ServerRequestInterface::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $response       = $this->createMock(ResponseInterface::class);

        $exception
            ->expects($this->once())
            ->method('buildJsonResponse')
            ->with($this->equalTo($response))
            ->will($this->returnValue($response))
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->will($this->returnValue($response))
        ;

        $strategy = new JsonStrategy($factory);

        $handler = $strategy->getNotFoundDecorator($exception);
        $this->assertInstanceOf(MiddlewareInterface::class, $handler);

        $actualResponse = $handler->process($request, $requestHandler);
        $this->assertInstanceOf(ResponseInterface::class, $actualResponse);
        $this->assertSame($response, $actualResponse);
    }

    /**
     * Asserts that the strategy returns the correct middleware to decorate MethodNotAllowedException.
     *
     * @return void
     */
    public function testStrategyReturnsCorrectMethodNotAllowedDecorator() : void
    {
        $exception      = $this->createMock(MethodNotAllowedException::class);
        $request        = $this->createMock(ServerRequestInterface::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $response       = $this->createMock(ResponseInterface::class);

        $exception
            ->expects($this->once())
            ->method('buildJsonResponse')
            ->with($this->equalTo($response))
            ->will($this->returnValue($response))
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->will($this->returnValue($response))
        ;

        $strategy = new JsonStrategy($factory);

        $handler = $strategy->getMethodNotAllowedDecorator($exception);
        $this->assertInstanceOf(MiddlewareInterface::class, $handler);

        $actualResponse = $handler->process($request, $requestHandler);
        $this->assertInstanceOf(ResponseInterface::class, $actualResponse);
        $this->assertSame($response, $actualResponse);
    }

    /**
     * Asserts that the strategy returns the correct exception handler middleware.
     *
     * @return void
     */
    public function testStrategyReturnsCorrectExceptionHandler() : void
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
            ->will($this->returnValue($response))
        ;

        $strategy = new JsonStrategy($factory);

        $handler = $strategy->getExceptionHandler();
        $this->assertInstanceOf(MiddlewareInterface::class, $handler);

        $actualResponse = $handler->process($request, $requestHandler);
        $this->assertInstanceOf(ResponseInterface::class, $actualResponse);
        $this->assertSame($response, $actualResponse);
    }

    /**
     * Asserts that the strategy returns the correct http exception handler middleware.
     *
     * @return void
     */
    public function testStrategyReturnsCorrectHttpExceptionHandler() : void
    {
        $exception      = $this->createMock(HttpException::class);
        $request        = $this->createMock(ServerRequestInterface::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $response       = $this->createMock(ResponseInterface::class);

        $exception
            ->expects($this->once())
            ->method('buildJsonResponse')
            ->with($this->equalTo($response))
            ->will($this->returnValue($response))
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
            ->will($this->returnValue($response))
        ;

        $strategy = new JsonStrategy($factory);

        $handler = $strategy->getExceptionHandler();
        $this->assertInstanceOf(MiddlewareInterface::class, $handler);

        $actualResponse = $handler->process($request, $requestHandler);
        $this->assertInstanceOf(ResponseInterface::class, $actualResponse);
        $this->assertSame($response, $actualResponse);
    }
}
