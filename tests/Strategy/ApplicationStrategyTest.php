<?php declare(strict_types=1);

namespace League\Route\Strategy;

use Exception;
use League\Route\Http\Exception\{MethodNotAllowedException, NotFoundException};
use League\Route\Route;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class ApplicationStrategyTest extends TestCase
{
    /**
     * Asserts that the strategy includes default headers.
     *
     * @return void
     */
    public function testStrategyHasDefaultHeaders(): void
    {
        $strategy = new ApplicationStrategy();

        $expectedHeaders = [];

        $this->assertSame($expectedHeaders, $strategy->getDefaultResponseHeaders());
    }

    /**
     * Asserts that the strategy default headers can be added to.
     *
     * @return void
     */
    public function testStrategyCanDefineAdditionalHeaders(): void
    {
        $strategy = new ApplicationStrategy();

        $additionalHeaders = [
            'cache-control' => 'no-cache',
        ];

        $strategy->addDefaultResponseHeaders($additionalHeaders);

        $expectedHeaders = array_replace([], $additionalHeaders);

        $this->assertSame($expectedHeaders, $strategy->getDefaultResponseHeaders());
    }

    /**
     * Asserts that the strategy properly invokes the route callable
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

        $strategy = new ApplicationStrategy;
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
        $this->expectException(NotFoundException::class);

        $exception      = $this->createMock(NotFoundException::class);
        $request        = $this->createMock(ServerRequestInterface::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);

        $strategy  = new ApplicationStrategy;
        $decorator = $strategy->getNotFoundDecorator($exception);

        $this->assertInstanceOf(MiddlewareInterface::class, $decorator);

        $decorator->process($request, $requestHandler);
    }

    /**
     * Asserts that the strategy returns the correct middleware to decorate MethodNotAllowedException.
     *
     * @return void
     */
    public function testStrategyReturnsCorrectMethodNotAllowedDecorator(): void
    {
        $this->expectException(MethodNotAllowedException::class);

        $exception      = $this->createMock(MethodNotAllowedException::class);
        $request        = $this->createMock(ServerRequestInterface::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);

        $strategy  = new ApplicationStrategy;
        $decorator = $strategy->getMethodNotAllowedDecorator($exception);

        $this->assertInstanceOf(MiddlewareInterface::class, $decorator);

        $decorator->process($request, $requestHandler);
    }

    /**
     * Asserts that the strategy returns the correct exception handler middleware.
     *
     * @return void
     */
    public function testStrategyReturnsCorrectExceptionHandler(): void
    {
        $this->expectException(Exception::class);

        $request        = $this->createMock(ServerRequestInterface::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);

        $requestHandler
            ->expects($this->once())
            ->method('handle')
            ->with($this->equalTo($request))
            ->will($this->throwException(new Exception))
        ;

        $strategy = new ApplicationStrategy;
        $handler  = $strategy->getExceptionHandler();

        $this->assertInstanceOf(MiddlewareInterface::class, $handler);

        $handler->process($request, $requestHandler);
    }
}
