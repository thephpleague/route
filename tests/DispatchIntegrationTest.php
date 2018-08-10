<?php declare(strict_types=1);

namespace League\Route;

use Exception;
use League\Route\Http\Exception\{BadRequestException, MethodNotAllowedException, NotFoundException};
use League\Route\Router;
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
    public function testDispatchesFoundRoute() : void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue('/example/route'))
        ;

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('GET'))
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->will($this->returnValue($uri))
        ;

        $router = new Router;

        $router->map('GET', '/example/{something}', function (
            ServerRequestInterface $request,
            array $args
        ) use (
            $response
        ) : ResponseInterface {
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
    public function testDispatchesExceptionRoute() : void
    {
        $this->expectException(Exception::class);

        $router = new Router;

        $router->map('GET', '/example/route', function () {
            throw new Exception;
        });

        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue('/example/route'))
        ;

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('GET'))
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->will($this->returnValue($uri))
        ;

        $router->dispatch($request);
    }

    /**
     * Asserts that the collection/dispatcher can filter through to exception decorator with the json strategy.
     *
     * @return void
     */
    public function testDispatchesExceptionWithJsonStrategyRoute() : void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);
        $body     = $this->createMock(StreamInterface::class);

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue('/example/route'))
        ;

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('GET'))
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->will($this->returnValue($uri))
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
            ->with($this->equalTo(500), $this->equalTo('Blah'))
            ->will($this->returnSelf())
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->will($this->returnValue($response))
        ;

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
    public function testDispatchesHttpExceptionWithJsonStrategyRoute() : void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);
        $body     = $this->createMock(StreamInterface::class);

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue('/example/route'))
        ;

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('GET'))
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->will($this->returnValue($uri))
        ;

        $body
            ->expects($this->once())
            ->method('isWritable')
            ->will($this->returnValue(true))
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
            ->with($this->equalTo(400), $this->equalTo('Bad Request'))
            ->will($this->returnSelf())
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->will($this->returnValue($response))
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
    public function testDispatchesNotFoundRoute() : void
    {
        $this->expectException(NotFoundException::class);

        $router = new Router;

        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue('/example/route'))
        ;

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('GET'))
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->will($this->returnValue($uri))
        ;

        $router->dispatch($request);
    }

    /**
     * Asserts that the collection/dispatcher can dispatch to a not found route with json strategy.
     *
     * @return void
     */
    public function testDispatchesNotFoundRouteWithJsonStrategy() : void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);
        $body     = $this->createMock(StreamInterface::class);

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue('/example/route'))
        ;

        $body
            ->expects($this->once())
            ->method('isWritable')
            ->will($this->returnValue(true))
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
            ->will($this->returnValue('GET'))
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->will($this->returnValue($uri))
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
            ->will($this->returnValue($body))
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->will($this->returnValue($response))
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
    public function testDispatchesNotAllowedRoute() : void
    {
        $this->expectException(MethodNotAllowedException::class);

        $router = new Router;

        $router->map('GET', '/example/{something}', function (ServerRequestInterface $request, array $args) {
            return $response;
        });

        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue('/example/route'))
        ;

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('POST'))
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->will($this->returnValue($uri))
        ;

        $router->dispatch($request);
    }

    /**
     * Asserts that the collection/dispatcher can dispatch to a not allowed route with json strategy.
     *
     * @return void
     */
    public function testDispatchesNotAllowedRouteWithJsonStrategy() : void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uri      = $this->createMock(UriInterface::class);
        $body     = $this->createMock(StreamInterface::class);

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue('/example/route'))
        ;

        $body
            ->expects($this->once())
            ->method('isWritable')
            ->will($this->returnValue(true))
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
            ->will($this->returnValue('POST'))
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->will($this->returnValue($uri))
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
            ->will($this->returnValue($body))
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->will($this->returnValue($response))
        ;

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
    public function testRoutesDoesNotPrepMismatchedScheme()
    {
        $this->expectException(Http\Exception\NotFoundException::class);

        $request = $this->createMock(ServerRequestInterface::class);
        $uri     = $this->createMock(UriInterface::class);

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('GET'))
        ;

        $request
            ->expects($this->exactly(3))
            ->method('getUri')
            ->will($this->returnValue($uri))
        ;

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue('/something'))
        ;

        $uri
            ->expects($this->once())
            ->method('getScheme')
            ->will($this->returnValue('http'))
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
    public function testRoutesDoesNotPrepMismatchedHost()
    {
        $this->expectException(Http\Exception\NotFoundException::class);

        $request = $this->createMock(ServerRequestInterface::class);
        $uri     = $this->createMock(UriInterface::class);

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('GET'))
        ;

        $request
            ->expects($this->exactly(3))
            ->method('getUri')
            ->will($this->returnValue($uri))
        ;

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue('/something'))
        ;

        $uri
            ->expects($this->once())
            ->method('getHost')
            ->will($this->returnValue('example.com'))
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
    public function testRoutesDoesNotPrepMismatchedPort()
    {
        $this->expectException(Http\Exception\NotFoundException::class);

        $request = $this->createMock(ServerRequestInterface::class);
        $uri     = $this->createMock(UriInterface::class);

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('GET'))
        ;

        $request
            ->expects($this->exactly(3))
            ->method('getUri')
            ->will($this->returnValue($uri))
        ;

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue('/something'))
        ;

        $uri
            ->expects($this->once())
            ->method('getPort')
            ->will($this->returnValue(80))
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
    public function testRouterSetsGroupStrategyOnGroupUriMatchButNoRouteMatch()
    {
        $this->expectException(Http\Exception\NotFoundException::class);

        $request = $this->createMock(ServerRequestInterface::class);
        $uri     = $this->createMock(UriInterface::class);

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('GET'))
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->will($this->returnValue($uri))
        ;

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue('/group/something'))
        ;

        $factory = $this->createMock(ResponseFactoryInterface::class);

        $router = (new Router)->setStrategy(new JsonStrategy($factory));

        $router->group('/group', function ($r) {
            $r->get('/', function () {
            });
        })->setStrategy(new Strategy\ApplicationStrategy);

        $router->dispatch($request);
        $this->assertInstanceOf(Strategy\ApplicationStrategy::class, $router->getStrategy());
    }

    /**
     * Asserts that middleware is invoked in the correct order.
     *
     * @return void
     */
    public function testMiddlewareIsOrderedCorrectly()
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
            public function __consttruct($counter, $phpunit)
            {
                $phpunit->assertSame($counter->getCounter(), 1);
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ) : ResponseInterface {
                return $handler->handle($request);
            }
        };

        $middlewareTwo = new class($counter, $this) implements MiddlewareInterface
        {
            public function __consttruct($counter, $phpunit)
            {
                $phpunit->assertSame($counter->getCounter(), 2);
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ) : ResponseInterface {
                return $handler->handle($request);
            }
        };

        $middlewareThree = new class($counter, $this) implements MiddlewareInterface
        {
            public function __consttruct($counter, $phpunit)
            {
                $phpunit->assertSame($counter->getCounter(), 3);
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ) : ResponseInterface {
                return $handler->handle($request);
            }
        };

        $response = $this->createMock(ResponseInterface::class);
        $request  = $this->createMock(ServerRequestInterface::class);
        $uri      = $this->createMock(UriInterface::class);

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('GET'))
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->will($this->returnValue($uri))
        ;

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue('/group/route'))
        ;

        $router = new Router;

        $router->middleware($middlewareOne);

        $router->group('/group', function ($r) use ($response, $middlewareThree) : void {
            $r->get('/route', function (ServerRequestInterface $request) use ($response) : ResponseInterface {
                return $response;
            })->middleware($middlewareThree);
        })->middleware($middlewareTwo);

        $router->dispatch($request);
    }
}
