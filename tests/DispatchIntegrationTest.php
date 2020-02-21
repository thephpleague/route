<?php declare(strict_types=1);

namespace League\Route;

use Exception;
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
    /**
     * Asserts that the collection/dispatcher can dispatch to a found route.
     *
     * @return void
     */
    public function testDispatchesFoundRoute(): void
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
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET')
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->willReturn($uri)
        ;

        $request
            ->expects($this->once())
            ->method('withAttribute')
            ->willReturn($request)
        ;

        $router = new Router;

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
    }

    /**
     * Asserts that the collection/dispatcher can filter through to exception decorator.
     *
     * @return void
     */
    public function testDispatchesExceptionRoute(): void
    {
        $this->expectException(Exception::class);

        $router = new Router;

        $router->map('GET', '/example/route', function () {
            throw new Exception;
        });

        $request = $this->createMock(ServerRequestInterface::class);
        $uri     = $this->createMock(UriInterface::class);

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->willReturn('/example/route')
        ;

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET')
        ;

        $request
            ->expects($this->any())
            ->method('withQueryParams')
            ->willReturn($request)
        ;

        $request
            ->expects($this->any())
            ->method('withAttribute')
            ->willReturn($request)
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->willReturn($uri)
        ;

        $router->dispatch($request);
    }

    /**
     * Asserts that the collection/dispatcher can filter through to exception decorator with the json strategy.
     *
     * @return void
     */
    public function testDispatchesExceptionWithJsonStrategyRoute(): void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);
        $body     = $this->createMock(StreamInterface::class);

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->willReturn('/example/route')
        ;

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
            ->will($this->returnSelf())
        ;

        $response
            ->expects($this->once())
            ->method('withStatus')
            ->with($this->equalTo(500), $this->equalTo('Blah'))
            ->will($this->returnSelf())
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->willReturn($response)
        ;

        /** @var Router $router */
        $router = (new Router)->setStrategy(new JsonStrategy($factory));

        $router->map('GET', '/example/route', function () {
            throw new Exception('Blah');
        });

        $resultResponse = $router->dispatch($request);

        $this->assertSame($response, $resultResponse);
    }

    /**
     * Asserts that the collection/dispatcher can filter through exception decorator
     * for http exception with the json strategy.
     *
     * @return void
     */
    public function testDispatchesHttpExceptionWithJsonStrategyRoute(): void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);
        $body     = $this->createMock(StreamInterface::class);

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->willReturn('/example/route')
        ;

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
            ->will($this->returnSelf())
        ;

        $response
            ->expects($this->once())
            ->method('withStatus')
            ->with($this->equalTo(400), $this->equalTo('Bad Request'))
            ->will($this->returnSelf())
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->willReturn($response)
        ;

        $router = (new Router)->setStrategy(new JsonStrategy($factory));

        $router->map('GET', '/example/route', function () {
            throw new BadRequestException;
        });

        $resultResponse = $router->dispatch($request);

        $this->assertSame($response, $resultResponse);
    }

    /**
     * Asserts that the collection/dispatcher can dispatch to a not found route.
     *
     * @return void
     */
    public function testDispatchesNotFoundRoute(): void
    {
        $this->expectException(NotFoundException::class);

        $router = new Router;

        $request = $this->createMock(ServerRequestInterface::class);
        $uri     = $this->createMock(UriInterface::class);

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->willReturn('/example/route')
        ;

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

        $router->dispatch($request);
    }

    /**
     * Asserts that the collection/dispatcher can dispatch to a not found route with json strategy.
     *
     * @return void
     */
    public function testDispatchesNotFoundRouteWithJsonStrategy(): void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);
        $body     = $this->createMock(StreamInterface::class);

        $uri
            ->expects($this->exactly(2))
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
            ->expects($this->exactly(2))
            ->method('getUri')
            ->willReturn($uri)
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
            ->willReturn($body)
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->willReturn($response)
        ;

        $router = (new Router)->setStrategy(new JsonStrategy($factory));

        $returnedResponse = $router->dispatch($request);

        $this->assertSame($response, $returnedResponse);
    }

    /**
     * Asserts that the collection/dispatcher can dispatch to a not allowed route.
     *
     * @return void
     */
    public function testDispatchesNotAllowedRoute(): void
    {
        $this->expectException(MethodNotAllowedException::class);

        $router = new Router;

        $router->map('GET', '/example/{something}', function (ServerRequestInterface $request, array $args) {
            //
        });

        $request = $this->createMock(ServerRequestInterface::class);
        $uri     = $this->createMock(UriInterface::class);

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->willReturn('/example/route')
        ;

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST')
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->willReturn($uri)
        ;

        $router->dispatch($request);
    }

    /**
     * Asserts that the collection/dispatcher can dispatch to a not allowed route with json strategy.
     *
     * @return void
     */
    public function testDispatchesNotAllowedRouteWithJsonStrategy(): void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);
        $body     = $this->createMock(StreamInterface::class);

        $uri
            ->expects($this->exactly(2))
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
            ->expects($this->exactly(2))
            ->method('getUri')
            ->willReturn($uri)
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
            ->willReturn($body)
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->willReturn($response)
        ;

        /** @var Router $router */
        $router = (new Router)->setStrategy(new JsonStrategy($factory));

        $router->map('GET', '/example/{something}', function (ServerRequestInterface $request, array $args) {
            return $response;
        });

        $returnedResponse = $router->dispatch($request);

        $this->assertSame($response, $returnedResponse);
    }

    /**
     * Asserts that the router does not prep a route for a mismatched scheme.
     *
     * @return void
     */
    public function testRoutesDoesNotPrepMismatchedScheme(): void
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
            ->expects($this->exactly(3))
            ->method('getUri')
            ->willReturn($uri)
        ;

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->willReturn('/something')
        ;

        $uri
            ->expects($this->once())
            ->method('getScheme')
            ->willReturn('http')
        ;

        $router = new Router;

        $router->get('/something', function () {
        })->setScheme('https');

        $router->dispatch($request);
    }

    /**
     * Asserts that the router does not prep a route for a mismatched host.
     *
     * @return void
     */
    public function testRoutesDoesNotPrepMismatchedHost(): void
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
            ->expects($this->exactly(3))
            ->method('getUri')
            ->willReturn($uri)
        ;

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->willReturn('/something')
        ;

        $uri
            ->expects($this->once())
            ->method('getHost')
            ->willReturn('example.com')
        ;

        $router = new Router;

        $router->get('/something', function () {
        })->setHost('sub.example.com');

        $router->dispatch($request);
    }

    /**
     * Asserts that the router does not prep a route for a mismatched port.
     *
     * @return void
     */
    public function testRoutesDoesNotPrepMismatchedPort(): void
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
            ->expects($this->exactly(3))
            ->method('getUri')
            ->willReturn($uri)
        ;

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->willReturn('/something')
        ;

        $uri
            ->expects($this->once())
            ->method('getPort')
            ->willReturn(80)
        ;

        $router = new Router;

        $router->get('/something', function () {
        })->setPort(8080);

        $router->dispatch($request);
    }

    /**
     * Asserts that the group strategy is set when the group URI matches but no route is matched.
     *
     * @return void
     */
    public function testRouterSetsGroupStrategyOnGroupUriMatchButNoRouteMatch(): void
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
            ->expects($this->exactly(2))
            ->method('getPath')
            ->willReturn('/group/something')
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);


        /** @var Router $router */
        $router = (new Router)->setStrategy(new JsonStrategy($factory));

        $router->group('/group', function ($r) {
            $r->get('/', function () {
            });
        })->setStrategy(new Strategy\ApplicationStrategy);

        $router->dispatch($request);
        $this->assertInstanceOf(Strategy\ApplicationStrategy::class, $router->getStrategy());
    }

    /**
     * Asserts that a strategy set on a route overrides the global strategy.
     *
     * @return void
     */
    public function testRouteStrategyOverridesGlobalStrategy(): void
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
            ->expects($this->exactly(2))
            ->method('getUri')
            ->willReturn($uri)
        ;

        $response
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($this->createMock(StreamInterface::class))
        ;

        $uri
            ->expects($this->exactly(2))
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
        $router = (new Router)->setStrategy(new Strategy\ApplicationStrategy);

        $router->map('GET', '/', function (): array {
            return [];
        })->setStrategy(new JsonStrategy($factory));

        $router->dispatch($request);
    }

    /**
     * Asserts that a strategy set on a route overrides its group's strategy.
     *
     * @return void
     */
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
            ->expects($this->exactly(2))
            ->method('getUri')
            ->willReturn($uri)
        ;

        $response
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($this->createMock(StreamInterface::class))
        ;

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->willReturn('/group/id')
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->exactly(2))
            ->method('createResponse')
            ->willReturn($response)
        ;

        $router = new Router;

        $router->group('/group', function ($r) use ($factory) {
            $r->get('/id', function (): array {
                return [];
            })->setStrategy(new JsonStrategy($factory));
        })->setStrategy(new Strategy\ApplicationStrategy);

        $router->dispatch($request);
    }

    /**
     * Asserts that middleware is invoked in the correct order.
     *
     * @return void
     */
    public function testMiddlewareIsOrderedCorrectly(): void
    {
        $counter = new class
        {
            private $counter = 0;

            public function getCounter()
            {
                return ++$this->counter;
            }
        };

        $middlewareOne = new class($counter, $this) implements MiddlewareInterface
        {
            /**
             * @param object   $counter
             * @param TestCase $phpunit
             */
            public function __construct($counter, $phpunit)
            {
                $phpunit->assertSame($counter->getCounter(), 1);
            }

            /**
             * @param ServerRequestInterface  $request
             * @param RequestHandlerInterface $handler
             *
             * @return ResponseInterface
             */
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $request->withRequestTarget('middleware1');
                return $handler->handle($request);
            }
        };

        $middlewareTwo = new class($counter, $this) implements MiddlewareInterface
        {
            /**
             * @param object   $counter
             * @param TestCase $phpunit
             */
            public function __construct($counter, $phpunit)
            {
                $phpunit->assertSame($counter->getCounter(), 2);
            }

            /**
             * @param ServerRequestInterface  $request
             * @param RequestHandlerInterface $handler
             *
             * @return ResponseInterface
             */
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $request->withRequestTarget('middleware2');
                return $handler->handle($request);
            }
        };

        $middlewareThree = new class($counter, $this) implements MiddlewareInterface
        {
            /**
             * @param object   $counter
             * @param TestCase $phpunit
             */
            public function __construct($counter, $phpunit)
            {
                $phpunit->assertSame($counter->getCounter(), 3);
            }

            /**
             * @param ServerRequestInterface  $request
             * @param RequestHandlerInterface $handler
             *
             * @return ResponseInterface
             */
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
            ->expects($this->exactly(2))
            ->method('getUri')
            ->willReturn($uri)
        ;

        $request
            ->expects($this->at(3))
            ->method('withRequestTarget')
            ->with($this->equalTo('middleware1'))
        ;

        $request
            ->expects($this->at(4))
            ->method('withRequestTarget')
            ->with($this->equalTo('middleware4'))
        ;

        $request
            ->expects($this->at(5))
            ->method('withRequestTarget')
            ->with($this->equalTo('middleware2'))
        ;

        $request
            ->expects($this->at(6))
            ->method('withRequestTarget')
            ->with($this->equalTo('middleware3'))
        ;

        $request
            ->expects($this->at(7))
            ->method('withRequestTarget')
            ->with($this->equalTo('middleware4'))
        ;

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->willReturn('/group/route')
        ;

        $router = new Router;

        $router
            ->middleware($middlewareOne)
            ->lazyMiddleware($middlewareFour)
        ;

        $router->group('/group', function ($r) use ($response, $middlewareThree, $middlewareFour): void {
            $r->get('/route', function (ServerRequestInterface $request) use ($response): ResponseInterface {
                return $response;
            })->middleware($middlewareThree)->lazyMiddleware($middlewareFour);
        })->middleware($middlewareTwo);

        $router->dispatch($request);
    }

    /**
     * Asserts that dispatcher always creates a Route object
     */
    public function testDispatchDoesNotThrowWhenUsingAddRoute(): void
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
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET')
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->willReturn($uri)
        ;

        $request
            ->expects($this->any())
            ->method('withAttribute')
            ->willReturn($request)
        ;

        $router = new Router;

        $router->addRoute(['GET', 'POST'], '/example/{something}', function (
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
    }
}
