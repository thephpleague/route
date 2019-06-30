<?php declare(strict_types=1);

namespace League\Route\Strategy;

use Exception;
use stdClass;
use League\Route\Http\Exception as HttpException;
use League\Route\Http\Exception\{MethodNotAllowedException, NotFoundException};
use League\Route\Route;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface, StreamInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class JsonStrategyTest extends TestCase
{
    /**
     * Asserts that the strategy includes default headers.
     *
     * @return void
     */
    public function testStrategyHasDefaultHeaders(): void
    {
        $factory = $this->createMock(ResponseFactoryInterface::class);

        $strategy = new JsonStrategy($factory);

        $expectedHeaders = [
            'content-type' => 'application/json',
        ];

        $this->assertSame($expectedHeaders, $strategy->getDefaultResponseHeaders());
    }

    /**
     * Asserts that the strategy default headers can be added to.
     *
     * @return void
     */
    public function testStrategyCanDefineAdditionalHeaders(): void
    {
        $factory = $this->createMock(ResponseFactoryInterface::class);

        $strategy = new JsonStrategy($factory);

        $additionalHeaders = [
            'cache-control' => 'no-cache',
        ];

        $strategy->addDefaultResponseHeaders($additionalHeaders);

        $expectedHeaders = array_replace([
            'content-type' => 'application/json',
        ], $additionalHeaders);

        $this->assertSame($expectedHeaders, $strategy->getDefaultResponseHeaders());
    }

    /**
     * Asserts that the strategy properly invokes the route callable.
     *
     * @return void
     */
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

    /**
     * Asserts that the strategy properly invokes the route callable with an array return.
     *
     * @return void
     */
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

    /**
     * Asserts that the strategy returns the correct middleware to decorate MethodNotAllowedException.
     *
     * @return void
     */
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

    /**
     * Asserts that the strategy returns the correct exception handler middleware.
     *
     * @return void
     */
    public function testStrategyReturnsCorrectExceptionHandler(): void
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

        $handler = $strategy->getExceptionHandler();

        $actualResponse = $handler->process($request, $requestHandler);
        $this->assertSame($response, $actualResponse);
    }

    /**
     * Asserts that the strategy returns the correct http exception handler middleware.
     *
     * @return void
     */
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

        $handler = $strategy->getExceptionHandler();

        $actualResponse = $handler->process($request, $requestHandler);
        $this->assertSame($response, $actualResponse);
    }

    /**
     * Asserts that the strategy properly invokes the route callable with an object return.
     *
     * @return void
     */
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
}
