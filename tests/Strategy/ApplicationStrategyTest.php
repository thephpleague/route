<?php

declare(strict_types=1);

namespace League\Route\Strategy;

use Exception;
use League\Route\Http\Exception\{MethodNotAllowedException, NotFoundException};
use League\Route\Route;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class ApplicationStrategyTest extends TestCase
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

        $strategy = new ApplicationStrategy();
        $response = $strategy->invokeRouteCallable($route, $expectedRequest);

        $this->assertSame($expectedResponse, $response);
    }

    public function testStrategyReturnsCorrectNotFoundDecorator(): void
    {
        $this->expectException(NotFoundException::class);

        $exception      = $this->createMock(NotFoundException::class);
        $request        = $this->createMock(ServerRequestInterface::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);

        $strategy  = new ApplicationStrategy();
        $decorator = $strategy->getNotFoundDecorator($exception);
        $decorator->process($request, $requestHandler);
    }

    public function testStrategyReturnsCorrectMethodNotAllowedDecorator(): void
    {
        $this->expectException(MethodNotAllowedException::class);

        $exception      = $this->createMock(MethodNotAllowedException::class);
        $request        = $this->createMock(ServerRequestInterface::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);

        $strategy  = new ApplicationStrategy();
        $decorator = $strategy->getMethodNotAllowedDecorator($exception);
        $decorator->process($request, $requestHandler);
    }

    public function testStrategyReturnsCorrectThrowableHandler(): void
    {
        $this->expectException(Exception::class);

        $request        = $this->createMock(ServerRequestInterface::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);

        $requestHandler
            ->expects($this->once())
            ->method('handle')
            ->with($this->equalTo($request))
            ->will($this->throwException(new Exception()))
        ;

        $strategy = new ApplicationStrategy();
        $handler  = $strategy->getThrowableHandler();
        $handler->process($request, $requestHandler);
    }
}
