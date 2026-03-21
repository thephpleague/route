<?php

declare(strict_types=1);

use League\Route\Http\Exception\BadRequestException;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Router;
use League\Route\Strategy\ApplicationStrategy;
use League\Route\Strategy\JsonStrategy;
use League\Route\Test\Fixture\Middleware;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

test('dispatches a found route and returns the response', function () {
    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/example/route');

    /** @var ResponseInterface&MockInterface $response */
    $response = Mockery::mock(ResponseInterface::class);

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);
    $request->allows('withAttribute')->andReturn($request);

    $router = new Router();

    $router->map('GET', '/example/{something}', function (
        ServerRequestInterface $request,
        array $args,
    ) use ($response): ResponseInterface {
        expect($args)->toBe(['something' => 'route']);
        return $response;
    });

    $returnedResponse = $router->handle($request);

    expect($returnedResponse)->toBe($response);
});

test('dispatches a found route multiple times and returns the response each time', function () {
    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/example/route');

    /** @var ResponseInterface&MockInterface $response */
    $response = Mockery::mock(ResponseInterface::class);

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);
    $request->allows('withAttribute')->andReturn($request);

    $router = new Router();

    $router->map('GET', '/example/{something}', function (
        ServerRequestInterface $request,
        array $args,
    ) use ($response): ResponseInterface {
        expect($args)->toBe(['something' => 'route']);
        return $response;
    });

    $firstResponse = $router->dispatch($request);
    expect($firstResponse)->toBe($response);

    $secondResponse = $router->dispatch($request);
    expect($secondResponse)->toBe($response);
});

test('dispatches a route that throws an exception and re-throws it', function () {
    $router = new Router();

    $router->map('GET', '/example/route', static function () {
        throw new Exception();
    });

    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/example/route');

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);
    $request->allows('withAttribute')->andReturn($request);

    $router->dispatch($request);
})->throws(Exception::class);

test('dispatches a route that throws a generic exception with json strategy and returns a json error response', function () {
    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/example/route');

    /** @var StreamInterface&MockInterface $body */
    $body = Mockery::mock(StreamInterface::class);
    $body->shouldReceive('write')->once()->with(json_encode([
        'status_code' => 500,
        'reason_phrase' => 'Blah',
    ]));

    /** @var ResponseInterface&MockInterface $response */
    $response = Mockery::mock(ResponseInterface::class);
    $response->shouldReceive('getBody')->once()->andReturn($body);
    $response->shouldReceive('withAddedHeader')->once()->with('content-type', 'application/json')->andReturn($response);
    $response->shouldReceive('withStatus')->once()->with(500, 'Blah')->andReturn($response);

    /** @var ResponseFactoryInterface&MockInterface $factory */
    $factory = Mockery::mock(ResponseFactoryInterface::class);
    $factory->shouldReceive('createResponse')->once()->andReturn($response);

    $router = (new Router())->setStrategy(new JsonStrategy($factory));

    $router->map('GET', '/example/route', static function () {
        throw new Exception('Blah');
    });

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);
    $request->allows('withAttribute')->andReturn($request);

    $resultResponse = $router->dispatch($request);

    expect($resultResponse)->toBe($response);
});

test('dispatches a route that throws an http exception with json strategy and returns a json error response', function () {
    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/example/route');

    /** @var StreamInterface&MockInterface $body */
    $body = Mockery::mock(StreamInterface::class);
    $body->shouldReceive('isWritable')->once()->andReturn(true);
    $body->shouldReceive('write')->once()->with(json_encode([
        'status_code' => 400,
        'reason_phrase' => 'Bad Request',
    ]));

    /** @var ResponseInterface&MockInterface $response */
    $response = Mockery::mock(ResponseInterface::class);
    $response->shouldReceive('getBody')->twice()->andReturn($body);
    $response->shouldReceive('withAddedHeader')->once()->with('content-type', 'application/json')->andReturn($response);
    $response->shouldReceive('withStatus')->once()->with(400, 'Bad Request')->andReturn($response);

    /** @var ResponseFactoryInterface&MockInterface $factory */
    $factory = Mockery::mock(ResponseFactoryInterface::class);
    $factory->shouldReceive('createResponse')->once()->andReturn($response);

    $router = (new Router())->setStrategy(new JsonStrategy($factory));

    $router->map('GET', '/example/route', static function () {
        throw new BadRequestException();
    });

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);
    $request->allows('withAttribute')->andReturn($request);

    $resultResponse = $router->dispatch($request);

    expect($resultResponse)->toBe($response);
});

test('dispatches a not found route and throws a NotFoundException', function () {
    $router = new Router();

    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/example/route');

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);

    $router->dispatch($request);
})->throws(NotFoundException::class);

test('dispatches a not found route with json strategy and returns a json 404 response', function () {
    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/example/route');

    /** @var StreamInterface&MockInterface $body */
    $body = Mockery::mock(StreamInterface::class);
    $body->shouldReceive('isWritable')->once()->andReturn(true);
    $body->shouldReceive('write')->once()->with(json_encode([
        'status_code' => 404,
        'reason_phrase' => 'Not Found',
    ]));

    /** @var ResponseInterface&MockInterface $response */
    $response = Mockery::mock(ResponseInterface::class);
    $response->shouldReceive('getBody')->twice()->andReturn($body);
    $response->shouldReceive('withAddedHeader')->once()->with('content-type', 'application/json')->andReturn($response);
    $response->shouldReceive('withStatus')->once()->with(404, 'Not Found')->andReturn($response);

    /** @var ResponseFactoryInterface&MockInterface $factory */
    $factory = Mockery::mock(ResponseFactoryInterface::class);
    $factory->shouldReceive('createResponse')->once()->andReturn($response);

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);

    $router = (new Router())->setStrategy(new JsonStrategy($factory));
    $returnedResponse = $router->dispatch($request);

    expect($returnedResponse)->toBe($response);
});

test('dispatches a method not allowed route and throws a MethodNotAllowedException', function () {
    $router = new Router();

    $router->map('GET', '/example/{something}', static function (ServerRequestInterface $request, array $args): void {});

    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/example/route');

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('POST');
    $request->allows('getUri')->andReturn($uri);

    $router->dispatch($request);
})->throws(MethodNotAllowedException::class);

test('dispatches a method not allowed route with json strategy and returns a json 405 response', function () {
    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/example/route');

    /** @var StreamInterface&MockInterface $body */
    $body = Mockery::mock(StreamInterface::class);
    $body->shouldReceive('isWritable')->once()->andReturn(true);
    $body->shouldReceive('write')->once()->with(json_encode([
        'status_code' => 405,
        'reason_phrase' => 'Method Not Allowed',
    ]));

    /** @var ResponseInterface&MockInterface $response */
    $response = Mockery::mock(ResponseInterface::class);
    $response->shouldReceive('getBody')->twice()->andReturn($body);
    $response->shouldReceive('withAddedHeader')->twice()->andReturn($response);
    $response->shouldReceive('withStatus')->once()->with(405, 'Method Not Allowed')->andReturn($response);

    /** @var ResponseFactoryInterface&MockInterface $factory */
    $factory = Mockery::mock(ResponseFactoryInterface::class);
    $factory->shouldReceive('createResponse')->once()->andReturn($response);

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('POST');
    $request->allows('getUri')->andReturn($uri);

    $router = (new Router())->setStrategy(new JsonStrategy($factory));

    $router->map('GET', '/example/{something}', static function (ServerRequestInterface $request, array $args): void {});

    $returnedResponse = $router->dispatch($request);

    expect($returnedResponse)->toBe($response);
});

test('router does not prepare a route when the scheme condition does not match', function () {
    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/something');
    $uri->allows('getScheme')->andReturn('http');

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);

    $router = new Router();
    $router->get('/something', static function () {})->setScheme('https');

    $router->dispatch($request);
})->throws(NotFoundException::class);

test('router does not match a route when the host condition does not match', function () {
    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/something');
    $uri->allows('getHost')->andReturn('example.com');

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);

    $router = new Router();
    $router->get('/something', static function () {})->setHost('sub.example.com');

    $router->dispatch($request);
})->throws(NotFoundException::class);

test('router does not match a route when the port condition does not match', function () {
    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/something');
    $uri->allows('getPort')->andReturn(80);

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);

    $router = new Router();
    $router->get('/something', static function () {})->setPort(8080);

    $router->dispatch($request);
})->throws(NotFoundException::class);

test('router uses global strategy when a group prefix matches but no route matches', function () {
    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/group/something');

    /** @var StreamInterface&MockInterface $body */
    $body = Mockery::mock(StreamInterface::class);
    $body->shouldReceive('isWritable')->once()->andReturn(true);
    $body->shouldReceive('write')->once()->with(json_encode([
        'status_code' => 404,
        'reason_phrase' => 'Not Found',
    ]));

    /** @var ResponseInterface&MockInterface $response */
    $response = Mockery::mock(ResponseInterface::class);
    $response->shouldReceive('getBody')->twice()->andReturn($body);
    $response->shouldReceive('withAddedHeader')->once()->with('content-type', 'application/json')->andReturn($response);
    $response->shouldReceive('withStatus')->once()->with(404, 'Not Found')->andReturn($response);

    /** @var ResponseFactoryInterface&MockInterface $factory */
    $factory = Mockery::mock(ResponseFactoryInterface::class);
    $factory->shouldReceive('createResponse')->once()->andReturn($response);

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);

    $router = (new Router())->setStrategy(new JsonStrategy($factory));

    $router->group('/group', static function ($r): void {
        $r->get('/', static function () {});
    })->setStrategy(new ApplicationStrategy());

    $returnedResponse = $router->dispatch($request);

    expect($returnedResponse)->toBe($response);
});

test('a route strategy overrides the global router strategy', function () {
    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/');

    /** @var StreamInterface&MockInterface $body */
    $body = Mockery::mock(StreamInterface::class);
    $body->shouldReceive('write')->once();

    /** @var ResponseInterface&MockInterface $response */
    $response = Mockery::mock(ResponseInterface::class);
    $response->shouldReceive('getBody')->once()->andReturn($body);
    $response->shouldReceive('hasHeader')->once()->with('content-type')->andReturn(false);
    $response->shouldReceive('withHeader')->once()->with('content-type', 'application/json')->andReturn($response);

    /** @var ResponseFactoryInterface&MockInterface $factory */
    $factory = Mockery::mock(ResponseFactoryInterface::class);
    $factory->shouldReceive('createResponse')->twice()->andReturn($response);

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);
    $request->allows('withAttribute')->andReturn($request);

    $router = (new Router())->setStrategy(new ApplicationStrategy());

    $router->map('GET', '/', static function (): array {
        return [];
    })->setStrategy(new JsonStrategy($factory));

    $returnedResponse = $router->dispatch($request);

    expect($returnedResponse)->toBe($response);
});

test('a route strategy overrides the group strategy', function () {
    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/group/id');

    /** @var StreamInterface&MockInterface $body */
    $body = Mockery::mock(StreamInterface::class);
    $body->shouldReceive('write')->once();

    /** @var ResponseInterface&MockInterface $response */
    $response = Mockery::mock(ResponseInterface::class);
    $response->shouldReceive('getBody')->once()->andReturn($body);
    $response->shouldReceive('hasHeader')->once()->with('content-type')->andReturn(false);
    $response->shouldReceive('withHeader')->once()->with('content-type', 'application/json')->andReturn($response);

    /** @var ResponseFactoryInterface&MockInterface $factory */
    $factory = Mockery::mock(ResponseFactoryInterface::class);
    $factory->shouldReceive('createResponse')->twice()->andReturn($response);

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);
    $request->allows('withAttribute')->andReturn($request);

    $router = new Router();

    $router->group('/group', function ($r) use ($factory): void {
        $r->get('/id', static function (): array {
            return [];
        })->setStrategy(new JsonStrategy($factory));
    })->setStrategy(new ApplicationStrategy());

    $returnedResponse = $router->dispatch($request);

    expect($returnedResponse)->toBe($response);
});

test('middleware is instantiated in the correct order', function () {
    $instantiationOrder = [];

    $middlewareOne = new class ($instantiationOrder) implements MiddlewareInterface {
        public function __construct(private array &$instantiationOrder)
        {
            $this->instantiationOrder[] = 1;
        }

        public function process(
            ServerRequestInterface $request,
            RequestHandlerInterface $handler,
        ): ResponseInterface {
            $request->withRequestTarget('middleware1');
            return $handler->handle($request);
        }
    };

    $middlewareTwo = new class ($instantiationOrder) implements MiddlewareInterface {
        public function __construct(private array &$instantiationOrder)
        {
            $this->instantiationOrder[] = 2;
        }

        public function process(
            ServerRequestInterface $request,
            RequestHandlerInterface $handler,
        ): ResponseInterface {
            $request->withRequestTarget('middleware2');
            return $handler->handle($request);
        }
    };

    $middlewareThree = new class ($instantiationOrder) implements MiddlewareInterface {
        public function __construct(private array &$instantiationOrder)
        {
            $this->instantiationOrder[] = 3;
        }

        public function process(
            ServerRequestInterface $request,
            RequestHandlerInterface $handler,
        ): ResponseInterface {
            $request->withRequestTarget('middleware3');
            return $handler->handle($request);
        }
    };

    expect($instantiationOrder)->toBe([1, 2, 3]);

    $middlewareFour = Middleware::class;

    /** @var ResponseInterface&MockInterface $response */
    $response = Mockery::mock(ResponseInterface::class);

    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getPath')->andReturn('/group/route');

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('getUri')->andReturn($uri);
    $request->allows('withRequestTarget')->andReturn($request);
    $request->allows('withAttribute')->andReturn($request);

    $router = new Router();

    $router
        ->middleware($middlewareOne)
        ->lazyPrependMiddleware($middlewareFour)
    ;

    $router->group('/group', static function ($r) use ($response, $middlewareThree, $middlewareFour): void {
        $r->get('/route', static function (ServerRequestInterface $request) use ($response): ResponseInterface {
            return $response;
        })->middleware($middlewareThree)->lazyMiddlewares([$middlewareFour]);
    })->middleware($middlewareTwo);

    $router->dispatch($request);
});

test('router can map a route with a host condition and dispatches it correctly', function () {
    /** @var UriInterface&MockInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->allows('getHost')->andReturn('test1.com');
    $uri->allows('getPath')->andReturn('/');

    /** @var ResponseInterface&MockInterface $response */
    $response = Mockery::mock(ResponseInterface::class);
    $response->shouldReceive('withHeader')->once()->with('test', 'test')->andReturn($response);

    /** @var ServerRequestInterface&MockInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->allows('getUri')->andReturn($uri);
    $request->allows('getMethod')->andReturn('GET');
    $request->allows('withAttribute')->andReturn($request);

    $router = new Router();

    $router->get('/', static function (ServerRequestInterface $request) use ($response): ResponseInterface {
        return $response->withHeader('test', 'test');
    })->setHost('test1.com');

    $result = $router->dispatch($request);

    expect($result)->toBe($response);
});
