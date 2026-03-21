<?php

declare(strict_types=1);

use League\Route\Http\Exception as HttpException;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Route;
use League\Route\Strategy\JsonStrategy;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

test('strategy invokes route callable returning a response and sets content-type header', function () {
    /** @var Route&MockInterface $route */
    $route = Mockery::mock(Route::class);

    /** @var ResponseInterface&MockInterface $expectedResponse */
    $expectedResponse = Mockery::mock(ResponseInterface::class);

    /** @var ServerRequestInterface&MockInterface $expectedRequest */
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

    $expectedResponse->shouldReceive('hasHeader')->once()->with('content-type')->andReturn(false);
    $expectedResponse->shouldReceive('withHeader')->once()->with('content-type', 'application/json')->andReturn($expectedResponse);

    /** @var ResponseFactoryInterface&MockInterface $factory */
    $factory = Mockery::mock(ResponseFactoryInterface::class);

    $strategy = new JsonStrategy($factory);
    $response = $strategy->invokeRouteCallable($route, $expectedRequest);

    expect($response)->toBe($expectedResponse);
});

test('strategy invokes route callable returning an array and encodes it as json body', function () {
    /** @var Route&MockInterface $route */
    $route = Mockery::mock(Route::class);

    /** @var ResponseInterface&MockInterface $expectedResponse */
    $expectedResponse = Mockery::mock(ResponseInterface::class);

    /** @var ServerRequestInterface&MockInterface $expectedRequest */
    $expectedRequest = Mockery::mock(ServerRequestInterface::class);

    /** @var StreamInterface&MockInterface $body */
    $body = Mockery::mock(StreamInterface::class);

    $expectedVars = ['something', 'else'];

    $route->shouldReceive('getCallable')->once()->andReturn(
        function (ServerRequestInterface $request, array $vars = []) use ($expectedRequest, $expectedVars): array {
            expect($request)->toBe($expectedRequest);
            expect($vars)->toBe($expectedVars);
            return [$vars[0] => $vars[1]];
        },
    );

    $route->shouldReceive('getVars')->once()->andReturn($expectedVars);

    $expectedResponse->shouldReceive('getBody')->once()->andReturn($body);
    $expectedResponse->shouldReceive('hasHeader')->once()->with('content-type')->andReturn(false);
    $expectedResponse->shouldReceive('withHeader')->once()->with('content-type', 'application/json')->andReturn($expectedResponse);

    $body->shouldReceive('write')->once()->with(json_encode([$expectedVars[0] => $expectedVars[1]]));

    /** @var ResponseFactoryInterface&MockInterface $factory */
    $factory = Mockery::mock(ResponseFactoryInterface::class);
    $factory->shouldReceive('createResponse')->once()->andReturn($expectedResponse);

    $strategy = new JsonStrategy($factory);
    $response = $strategy->invokeRouteCallable($route, $expectedRequest);

    expect($response)->toBe($expectedResponse);
});

test('strategy invokes route callable returning an object and encodes it as json body', function () {
    /** @var Route&MockInterface $route */
    $route = Mockery::mock(Route::class);

    /** @var ResponseInterface&MockInterface $expectedResponse */
    $expectedResponse = Mockery::mock(ResponseInterface::class);

    /** @var ServerRequestInterface&MockInterface $expectedRequest */
    $expectedRequest = Mockery::mock(ServerRequestInterface::class);

    /** @var StreamInterface&MockInterface $body */
    $body = Mockery::mock(StreamInterface::class);

    $expectedVars = ['something', 'else'];
    $expectedObject = new stdClass();
    $expectedObject->something = 'else';

    $route->shouldReceive('getCallable')->once()->andReturn(
        function (ServerRequestInterface $request) use ($expectedRequest, $expectedObject): stdClass {
            expect($request)->toBe($expectedRequest);
            return $expectedObject;
        },
    );

    $route->shouldReceive('getVars')->once()->andReturn($expectedVars);

    $expectedResponse->shouldReceive('getBody')->once()->andReturn($body);
    $expectedResponse->shouldReceive('hasHeader')->once()->with('content-type')->andReturn(false);
    $expectedResponse->shouldReceive('withHeader')->once()->with('content-type', 'application/json')->andReturn($expectedResponse);

    $body->shouldReceive('write')->once()->with(json_encode([$expectedVars[0] => $expectedVars[1]]));

    /** @var ResponseFactoryInterface&MockInterface $factory */
    $factory = Mockery::mock(ResponseFactoryInterface::class);
    $factory->shouldReceive('createResponse')->once()->andReturn($expectedResponse);

    $strategy = new JsonStrategy($factory);
    $response = $strategy->invokeRouteCallable($route, $expectedRequest);

    expect($response)->toBe($expectedResponse);
});

test('strategy not found decorator builds and returns a json response', function () {
    /** @var NotFoundException&MockInterface $exception */
    $exception = Mockery::mock(NotFoundException::class);

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    /** @var RequestHandlerInterface&MockInterface $requestHandler */
    $requestHandler = Mockery::mock(RequestHandlerInterface::class);

    /** @var ResponseInterface&MockInterface $response */
    $response = Mockery::mock(ResponseInterface::class);

    $exception->shouldReceive('buildJsonResponse')->once()->with($response)->andReturn($response);

    /** @var ResponseFactoryInterface&MockInterface $factory */
    $factory = Mockery::mock(ResponseFactoryInterface::class);
    $factory->shouldReceive('createResponse')->once()->andReturn($response);

    $strategy = new JsonStrategy($factory);
    $handler = $strategy->getNotFoundDecorator($exception);

    $actualResponse = $handler->process($request, $requestHandler);

    expect($actualResponse)->toBe($response);
});

test('strategy method not allowed decorator builds and returns a json response', function () {
    /** @var MethodNotAllowedException&MockInterface $exception */
    $exception = Mockery::mock(MethodNotAllowedException::class);

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    /** @var RequestHandlerInterface&MockInterface $requestHandler */
    $requestHandler = Mockery::mock(RequestHandlerInterface::class);

    /** @var ResponseInterface&MockInterface $response */
    $response = Mockery::mock(ResponseInterface::class);

    $exception->shouldReceive('buildJsonResponse')->once()->with($response)->andReturn($response);

    /** @var ResponseFactoryInterface&MockInterface $factory */
    $factory = Mockery::mock(ResponseFactoryInterface::class);
    $factory->shouldReceive('createResponse')->once()->andReturn($response);

    $strategy = new JsonStrategy($factory);
    $handler = $strategy->getMethodNotAllowedDecorator($exception);

    $actualResponse = $handler->process($request, $requestHandler);

    expect($actualResponse)->toBe($response);
});

test('strategy throwable handler returns a json error response for a generic exception', function () {
    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    /** @var RequestHandlerInterface&MockInterface $requestHandler */
    $requestHandler = Mockery::mock(RequestHandlerInterface::class);

    /** @var ResponseInterface&MockInterface $response */
    $response = Mockery::mock(ResponseInterface::class);

    /** @var StreamInterface&MockInterface $body */
    $body = Mockery::mock(StreamInterface::class);

    $requestHandler->shouldReceive('handle')->once()->with($request)->andThrow(new Exception('Exception thrown'));

    $response->shouldReceive('getBody')->once()->andReturn($body);
    $response->shouldReceive('withAddedHeader')->once()->with('content-type', 'application/json')->andReturn($response);
    $response->shouldReceive('withStatus')->once()->with(500, 'Exception thrown')->andReturn($response);

    $body->shouldReceive('write')->once()->with(json_encode([
        'status_code' => 500,
        'reason_phrase' => 'Exception thrown',
    ]));

    /** @var ResponseFactoryInterface&MockInterface $factory */
    $factory = Mockery::mock(ResponseFactoryInterface::class);
    $factory->shouldReceive('createResponse')->once()->andReturn($response);

    $strategy = new JsonStrategy($factory);
    $handler = $strategy->getThrowableHandler();
    $actualResponse = $handler->process($request, $requestHandler);

    expect($actualResponse)->toBe($response);
});

test('strategy throwable handler delegates to buildJsonResponse for an http exception', function () {
    /** @var HttpException&MockInterface $exception */
    $exception = Mockery::mock(HttpException::class);

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    /** @var RequestHandlerInterface&MockInterface $requestHandler */
    $requestHandler = Mockery::mock(RequestHandlerInterface::class);

    /** @var ResponseInterface&MockInterface $response */
    $response = Mockery::mock(ResponseInterface::class);

    $exception->shouldReceive('buildJsonResponse')->once()->with($response)->andReturn($response);
    $requestHandler->shouldReceive('handle')->once()->with($request)->andThrow($exception);

    /** @var ResponseFactoryInterface&MockInterface $factory */
    $factory = Mockery::mock(ResponseFactoryInterface::class);
    $factory->shouldReceive('createResponse')->once()->andReturn($response);

    $strategy = new JsonStrategy($factory);
    $handler = $strategy->getThrowableHandler();
    $actualResponse = $handler->process($request, $requestHandler);

    expect($actualResponse)->toBe($response);
});

test('strategy options callable returns a response with allow and access-control-allow-methods headers', function () {
    /** @var ResponseInterface&MockInterface $response */
    $response = Mockery::mock(ResponseInterface::class);

    /** @var ResponseFactoryInterface&MockInterface $factory */
    $factory = Mockery::mock(ResponseFactoryInterface::class);

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    $response->shouldReceive('withHeader')->twice()->andReturn($response);

    $factory->shouldReceive('createResponse')->once()->andReturn($response);

    $strategy = new JsonStrategy($factory);
    $callable = $strategy->getOptionsCallable(['GET', 'POST']);

    $result = $callable($request, []);

    expect($result)->toBe($response);
});
