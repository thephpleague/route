<?php

declare(strict_types=1);

use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Route;
use League\Route\Strategy\ApplicationStrategy;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

test('strategy invokes route callable and returns response', function () {
    /** @var Route&MockInterface $route */
    $route = Mockery::mock(Route::class);

    $expectedResponse = Mockery::mock(ResponseInterface::class);
    $expectedRequest = Mockery::mock(ServerRequestInterface::class);
    $expectedVars = ['something', 'else'];

    $route->shouldReceive('getCallable')->once()->andReturn(
        function (ServerRequestInterface $request, array $vars = []) use ($expectedRequest, $expectedResponse, $expectedVars): ResponseInterface {
            expect($request)->toBe($expectedRequest);
            expect($vars)->toBe($expectedVars);
            return $expectedResponse;
        },
    );

    $route->shouldReceive('getVars')->once()->andReturn($expectedVars);

    $strategy = new ApplicationStrategy();
    $response = $strategy->invokeRouteCallable($route, $expectedRequest);

    expect($response)->toBe($expectedResponse);
});

test('strategy not found decorator throws NotFoundException when processed', function () {
    /** @var NotFoundException&MockInterface $exception */
    $exception = Mockery::mock(NotFoundException::class);

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    /** @var RequestHandlerInterface&MockInterface $requestHandler */
    $requestHandler = Mockery::mock(RequestHandlerInterface::class);

    $strategy = new ApplicationStrategy();
    $decorator = $strategy->getNotFoundDecorator($exception);
    $decorator->process($request, $requestHandler);
})->throws(NotFoundException::class);

test('strategy method not allowed decorator throws MethodNotAllowedException when processed', function () {
    /** @var MethodNotAllowedException&MockInterface $exception */
    $exception = Mockery::mock(MethodNotAllowedException::class);

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    /** @var RequestHandlerInterface&MockInterface $requestHandler */
    $requestHandler = Mockery::mock(RequestHandlerInterface::class);

    $strategy = new ApplicationStrategy();
    $decorator = $strategy->getMethodNotAllowedDecorator($exception);
    $decorator->process($request, $requestHandler);
})->throws(MethodNotAllowedException::class);

test('strategy throwable handler re-throws exception from request handler', function () {
    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    /** @var RequestHandlerInterface&MockInterface $requestHandler */
    $requestHandler = Mockery::mock(RequestHandlerInterface::class);

    $requestHandler->shouldReceive('handle')->once()->with($request)->andThrow(new Exception());

    $strategy = new ApplicationStrategy();
    $handler = $strategy->getThrowableHandler();
    $handler->process($request, $requestHandler);
})->throws(Exception::class);
