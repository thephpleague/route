<?php

declare(strict_types=1);

namespace League\Route;

use Exception;
use League\Route\Fixture\Controller;
use League\Route\Fixture\Middleware;
use League\Route\Http\Exception\{BadRequestException, MethodNotAllowedException, NotFoundException};
use League\Route\Strategy\JsonStrategy;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{
    ResponseFactoryInterface, ResponseInterface, ServerRequestInterface, StreamInterface, UriInterface
};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class DispatchIntegrationTest extends TestCase
{
    public function testDispatchesFoundRoute(): void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);

        $uri
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('/example/route')
        ;

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET')
        ;

        $request
            ->expects($this->once())
            ->method('getUri')
            ->willReturn($uri)
        ;

        $request
            ->expects($this->once())
            ->method('withAttribute')
            ->willReturn($request)
        ;

        $router = new Router();

        $router->map('GET', '/example/{something}', function (
            ServerRequestInterface $request,
            array $args
        ) use (
            $response
        ): ResponseInterface {
            $this->assertSame([
                'something' => 'route'
            ], $args);

            return $response;
        });

        $returnedResponse = $router->handle($request);
        $this->assertSame($response, $returnedResponse);
    }

    public function testDispatchesFoundRouteMultipleTimes(): void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->willReturn('/example/route')
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getMethod')
            ->willReturn('GET')
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->willReturn($uri)
        ;

        $request
            ->expects($this->exactly(2))
            ->method('withAttribute')
            ->willReturn($request)
        ;

        $router = new Router();

        $router->map('GET', '/example/{something}', function (
            ServerRequestInterface $request,
            array $args
        ) use (
            $response
        ): ResponseInterface {
            $this->assertSame([
                'something' => 'route'
            ], $args);

            return $response;
        });

        $returnedResponse = $router->dispatch($request);
        $this->assertSame($response, $returnedResponse);

        $returnedResponse = $router->dispatch($request);
        $this->assertSame($response, $returnedResponse);
    }

    public function testDispatchesExceptionRoute(): void
    {
        $this->expectException(Exception::class);

        $router = new Router();

        $router->map('GET', '/example/route', static function () {
            throw new Exception();
        });

        $request = $this->createMock(ServerRequestInterface::class);
        $uri     = $this->createMock(UriInterface::class);

        $uri
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('/example/route')
        ;

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET')
        ;

        $request
            ->method('withQueryParams')
            ->willReturn($request)
        ;

        $request
            ->method('withAttribute')
            ->willReturn($request)
        ;

        $request
            ->expects($this->once())
            ->method('getUri')
            ->willReturn($uri)
        ;

        $router->dispatch($request);
    }

    public function testDispatchesExceptionWithJsonStrategyRoute(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $body = $this->createMock(StreamInterface::class);

        $uri
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('/example/route')
        ;

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET')
        ;

        $request
            ->expects($this->once())
            ->method('getUri')
            ->willReturn($uri)
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
            ->willReturn($body)
        ;

        $response
            ->expects($this->once())
            ->method('withAddedHeader')
            ->with($this->equalTo('content-type'), $this->equalTo('application/json'))
            ->willReturnSelf()
        ;

        $response
            ->expects($this->once())
            ->method('withStatus')
            ->with($this->equalTo(500), $this->equalTo('Blah'))
            ->willReturnSelf()
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->willReturn($response)
        ;

        /** @var Router $router */
        $router = (new Router())->setStrategy(new JsonStrategy($factory));

        $router->map('GET', '/example/route', function () {
            throw new Exception('Blah');
        });

        $resultResponse = $router->dispatch($request);
        $this->assertSame($response, $resultResponse);
    }

    public function testDispatchesHttpExceptionWithJsonStrategyRoute(): void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);
        $body     = $this->createMock(StreamInterface::class);

        $uri
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('/example/route')
        ;

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET')
        ;

        $request
            ->expects($this->once())
            ->method('getUri')
            ->willReturn($uri)
        ;

        $body
            ->expects($this->once())
            ->method('isWritable')
            ->willReturn(true)
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
            ->willReturn($body)
        ;

        $response
            ->expects($this->once())
            ->method('withAddedHeader')
            ->with($this->equalTo('content-type'), $this->equalTo('application/json'))
            ->willReturnSelf()
        ;

        $response
            ->expects($this->once())
            ->method('withStatus')
            ->with($this->equalTo(400), $this->equalTo('Bad Request'))
            ->willReturnSelf()
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->willReturn($response)
        ;

        $router = (new Router())->setStrategy(new JsonStrategy($factory));

        $router->map('GET', '/example/route', static function () {
            throw new BadRequestException();
        });

        $resultResponse = $router->dispatch($request);
        $this->assertSame($response, $resultResponse);
    }

    public function testDispatchesNotFoundRoute(): void
    {
        $this->expectException(NotFoundException::class);

        $router = new Router();

        $request = $this->createMock(ServerRequestInterface::class);
        $uri     = $this->createMock(UriInterface::class);

        $uri
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('/example/route')
        ;

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET')
        ;

        $request
            ->expects($this->once())
            ->method('getUri')
            ->willReturn($uri)
        ;

        $router->dispatch($request);
    }

    public function testDispatchesNotFoundRouteWithJsonStrategy(): void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);
        $body     = $this->createMock(StreamInterface::class);

        $uri
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('/example/route')
        ;

        $body
            ->expects($this->once())
            ->method('isWritable')
            ->willReturn(true)
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
            ->willReturn('GET')
        ;

        $request
            ->expects($this->once())
            ->method('getUri')
            ->willReturn($uri)
        ;

        $response
            ->expects($this->once())
            ->method('withAddedHeader')
            ->with($this->equalTo('content-type'), $this->equalTo('application/json'))
            ->willReturnSelf()
        ;

        $response
            ->expects($this->once())
            ->method('withStatus')
            ->with($this->equalTo(404), $this->equalTo('Not Found'))
            ->willReturnSelf()
        ;

        $response
            ->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn($body)
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->willReturn($response)
        ;

        $router = (new Router())->setStrategy(new JsonStrategy($factory));
        $returnedResponse = $router->dispatch($request);
        $this->assertSame($response, $returnedResponse);
    }

    public function testDispatchesNotAllowedRoute(): void
    {
        $this->expectException(MethodNotAllowedException::class);

        $router = new Router();

        $router->map('GET', '/example/{something}', function (ServerRequestInterface $request, array $args) {
            //
        });

        $request = $this->createMock(ServerRequestInterface::class);
        $uri     = $this->createMock(UriInterface::class);

        $uri
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('/example/route')
        ;

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST')
        ;

        $request
            ->expects($this->once())
            ->method('getUri')
            ->willReturn($uri)
        ;

        $router->dispatch($request);
    }

    public function testDispatchesNotAllowedRouteWithJsonStrategy(): void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);
        $body     = $this->createMock(StreamInterface::class);

        $uri
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('/example/route')
        ;

        $body
            ->expects($this->once())
            ->method('isWritable')
            ->willReturn(true)
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
            ->willReturn('POST')
        ;

        $request
            ->expects($this->once())
            ->method('getUri')
            ->willReturn($uri)
        ;

        $response
            ->expects($this->exactly(2))
            ->method('withAddedHeader')
            ->willReturnSelf()
        ;

        $response
            ->expects($this->once())
            ->method('withStatus')
            ->with($this->equalTo(405), $this->equalTo('Method Not Allowed'))
            ->willReturnSelf()
        ;

        $response
            ->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn($body)
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->willReturn($response)
        ;

        /** @var Router $router */
        $router = (new Router())->setStrategy(new JsonStrategy($factory));

        $router->map('GET', '/example/{something}', static function (ServerRequestInterface $request, array $args) {
            //
        });

        $returnedResponse = $router->dispatch($request);
        $this->assertSame($response, $returnedResponse);
    }

    public function testRouterDoesNotPrepareMismatchedScheme(): void
    {
        $this->expectException(Http\Exception\NotFoundException::class);

        $request = $this->createMock(ServerRequestInterface::class);
        $uri     = $this->createMock(UriInterface::class);

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET')
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->willReturn($uri)
        ;

        $uri
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('/something')
        ;

        $uri
            ->expects($this->once())
            ->method('getScheme')
            ->willReturn('http')
        ;

        $router = new Router();

        $router->get('/something', static function () {
        })->setScheme('https');
        $router->dispatch($request);
    }

    public function testRouterDoesNotMatchMismatchedHost(): void
    {
        $this->expectException(Http\Exception\NotFoundException::class);

        $request = $this->createMock(ServerRequestInterface::class);
        $uri     = $this->createMock(UriInterface::class);

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET')
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->willReturn($uri)
        ;

        $uri
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('/something')
        ;

        $uri
            ->expects($this->once())
            ->method('getHost')
            ->willReturn('example.com')
        ;

        $router = new Router();

        $router->get('/something', static function () {
        })->setHost('sub.example.com');
        $router->dispatch($request);
    }

    public function testRouterDoesNotMatchMismatchedPort(): void
    {
        $this->expectException(Http\Exception\NotFoundException::class);

        $request = $this->createMock(ServerRequestInterface::class);
        $uri     = $this->createMock(UriInterface::class);

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET')
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->willReturn($uri)
        ;

        $uri
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('/something')
        ;

        $uri
            ->expects($this->once())
            ->method('getPort')
            ->willReturn(80)
        ;

        $router = new Router();

        $router->get('/something', static function () {
        })->setPort(8080);
        $router->dispatch($request);
    }

    public function testRouterUsesGlobalStrategyWhenGroupPrefixMatchesButNoRouteMatches(): void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);
        $body     = $this->createMock(StreamInterface::class);

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET')
        ;

        $request
            ->expects($this->once())
            ->method('getUri')
            ->willReturn($uri)
        ;

        $uri
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('/group/something')
        ;

        $body
            ->expects($this->once())
            ->method('isWritable')
            ->willReturn(true)
        ;

        $body
            ->expects($this->once())
            ->method('write')
            ->with($this->equalTo(json_encode([
                'status_code'   => 404,
                'reason_phrase' => 'Not Found'
            ])))
        ;

        $response
            ->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn($body)
        ;

        $response
            ->expects($this->once())
            ->method('withAddedHeader')
            ->with($this->equalTo('content-type'), $this->equalTo('application/json'))
            ->willReturnSelf()
        ;

        $response
            ->expects($this->once())
            ->method('withStatus')
            ->with($this->equalTo(404), $this->equalTo('Not Found'))
            ->willReturnSelf()
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->willReturn($response)
        ;

        $router = (new Router())->setStrategy(new JsonStrategy($factory));

        $router->group('/group', function ($r) {
            $r->get('/', static function () {
            });
        })->setStrategy(new Strategy\ApplicationStrategy());

        $returnedResponse = $router->dispatch($request);
        $this->assertSame($response, $returnedResponse);
    }

    public function testRouteStrategyOverridesGlobalStrategy(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri = $this->createMock(UriInterface::class);

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET')
        ;

        $request
            ->expects($this->once())
            ->method('getUri')
            ->willReturn($uri)
        ;

        $response
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($this->createMock(StreamInterface::class))
        ;

        $response
            ->expects($this->once())
            ->method('withHeader')
            ->willReturnSelf()
        ;

        $uri
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('/')
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->exactly(2))
            ->method('createResponse')
            ->willReturn($response)
        ;

        /** @var Router $router */
        $router = (new Router())->setStrategy(new Strategy\ApplicationStrategy());

        $router->map('GET', '/', function (): array {
            return [];
        })->setStrategy(new JsonStrategy($factory));
        $router->dispatch($request);
    }

    public function testRouteStrategyOverridesGroupStrategy(): void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET')
        ;

        $request
            ->expects($this->once())
            ->method('getUri')
            ->willReturn($uri)
        ;

        $response
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($this->createMock(StreamInterface::class))
        ;

        $response
            ->expects($this->once())
            ->method('withHeader')
            ->willReturnSelf()
        ;

        $uri
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('/group/id')
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->exactly(2))
            ->method('createResponse')
            ->willReturn($response)
        ;

        $router = new Router();

        $router->group('/group', function ($r) use ($factory) {
            $r->get('/id', function (): array {
                return [];
            })->setStrategy(new JsonStrategy($factory));
        })->setStrategy(new Strategy\ApplicationStrategy());

        $router->dispatch($request);
    }

    public function testMiddlewareIsOrderedCorrectly(): void
    {
        $counter = new class ()
        {
            private $counter = 0;

            public function getCounter(): int
            {
                return ++$this->counter;
            }
        };

        $middlewareOne = new class ($counter, $this) implements MiddlewareInterface
        {
            public function __construct($counter, $phpunit)
            {
                $phpunit->assertSame($counter->getCounter(), 1);
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $request->withRequestTarget('middleware1');
                return $handler->handle($request);
            }
        };

        $middlewareTwo = new class ($counter, $this) implements MiddlewareInterface
        {
            public function __construct($counter, $phpunit)
            {
                $phpunit->assertSame($counter->getCounter(), 2);
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $request->withRequestTarget('middleware2');
                return $handler->handle($request);
            }
        };

        $middlewareThree = new class ($counter, $this) implements MiddlewareInterface
        {
            public function __construct($counter, $phpunit)
            {
                $phpunit->assertSame($counter->getCounter(), 3);
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $request->withRequestTarget('middleware3');
                return $handler->handle($request);
            }
        };

        $middlewareFour = Middleware::class;

        $response  = $this->createMock(ResponseInterface::class);
        $request   = $this->createMock(ServerRequestInterface::class);
        $uri       = $this->createMock(UriInterface::class);

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET')
        ;

        $request
            ->expects($this->once())
            ->method('getUri')
            ->willReturn($uri)
        ;

        $request
            ->expects($this->exactly(5))
            ->method('withRequestTarget')
            ->with($this->matchesRegularExpression('/middleware[1-4]/'))
        ;

        $uri
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('/group/route')
        ;

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
    }

    public function testCanMapRouteWithHostCondition(): void
    {
        $router = new Router();

        $response = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $response->expects(self::once())->method('withHeader')->willReturnSelf();

        $router
            ->get('/', static function (ServerRequestInterface $request) use ($response): ResponseInterface {
                return $response->withHeader('test', 'test');
            })
            ->setHost('test1.com')
        ;

        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $uri = $this->getMockBuilder(UriInterface::class)->getMock();

        $uri->method('getHost')->willReturn('test1.com');
        $uri->method('getPath')->willReturn('/');

        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('GET');

        $router->dispatch($request);
    }
}
