<?php

namespace League\Route\Test;

use League\Route;
use League\Route\Http\Exception as HttpException;
use League\Route\Strategy\MethodArgumentStrategy;
use League\Route\Strategy\RestfulStrategy;
use League\Route\Strategy\RequestResponseStrategy;
use League\Route\Strategy\UriStrategy;

class DispatcherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Assert that a route using the Restful Strategy returns a json response
     * when a http exception is thrown
     *
     * @return void
     */
    public function testRestfulStrategyReturnsJsonResponseWhenHttpExceptionIsThrown()
    {
        $controller = $this->getMock('SomeClass', ['someMethod']);

        $controller->expects($this->once())
                   ->method('someMethod')
                   ->will($this->throwException(new HttpException\ConflictException));

        $container = $this->getMock('League\Container\Container');

        $container->expects($this->at(1))
                  ->method('get')
                  ->with($this->equalTo('SomeClass'))
                  ->will($this->returnValue($controller));

        $collection = new Route\RouteCollection($container);
        $collection->setStrategy(new RestfulStrategy);

        $collection->get('/route', 'SomeClass::someMethod');
        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('{"status_code":409,"message":"Conflict"}', $response->getContent());
    }

    /**
     * Assert that a route using Restful Strategy throws exception for wrong response type
     *
     * @return void
     */
    public function testRestfulStrategyRouteThrowsExceptionWhenWrongResponseReturned()
    {
        $this->setExpectedException('RuntimeException');

        $collection = new Route\RouteCollection;
        $collection->setStrategy(new RestfulStrategy);

        $collection->get('/route', function ($request) {
            return new \stdClass;
        });

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');
    }

    /**
     * Assert that a route using the Restful Strategy gets passed the correct arguments
     *
     * @return void
     */
    public function testRestfulStrategyReceivesCorrectArguments()
    {
        $collection = new Route\RouteCollection;
        $collection->setStrategy(new RestfulStrategy);

        $collection->get('/route', function ($request) {
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Request', $request);
            return new \ArrayObject;
        });

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
    }

    /**
     * Assert that a route using the Restful Strategy returns response when controller does
     *
     * @return void
     */
    public function testRestfulStrategyRouteReturnsResponseWhenControllerDoes()
    {
        $mockResponse = $this->getMock('Symfony\Component\HttpFoundation\JsonResponse');

        $collection = new Route\RouteCollection;

        $collection->setStrategy(new RestfulStrategy);

        $collection->get('/route/{id}/{name}', function ($request) use ($mockResponse) {
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Request', $request);
            return $mockResponse;
        });

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route/2/phil');

        $this->assertSame($mockResponse, $response);
    }

    /**
     * Asserts that the correct method is invoked on a class based controller
     *
     * @return void
     */
    public function testClassBasedControllerInvokesCorrectMethod()
    {
        $controller = $this->getMock('SomeClass', ['someMethod']);

        $controller->expects($this->once())
                   ->method('someMethod')
                   ->with($this->equalTo('2'), $this->equalTo('phil'))
                   ->will($this->returnValue('hello world'));

        $container = $this->getMock('League\Container\ContainerInterface');

        $container->expects($this->once())
                  ->method('get')
                  ->with($this->equalTo('SomeClass'))
                  ->will($this->returnValue($controller));

        $collection = new Route\RouteCollection($container);
        $collection->setStrategy(new UriStrategy);
        $collection->get('/route/{id}/{name}', 'SomeClass::someMethod');

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route/2/phil');

        $this->assertEquals('hello world', $response->getContent());
    }

    /**
     * Assert that an exception is thrown when no controller method is specified
     *
     * @return void
     */
    public function testClassBasedControllerRouteThrowsExceptionWhenNoFunctionPresent()
    {
        $this->setExpectedException('RuntimeException');

        $container = $this->getMock('League\Container\ContainerInterface');

        $collection = new Route\RouteCollection($container);
        $collection->setStrategy(new UriStrategy);

        $collection->get('/route/', 'SomeClass');
        $dispatcher = $collection->getDispatcher();

        $response = $dispatcher->dispatch('GET', '/route/');
    }

    /**
     * Assert that a route using the URI Strategy gets passed the correct arguments
     *
     * @return void
     */
    public function testUriStrategyRouteReceivesCorrectArguments()
    {
        $collection = new Route\RouteCollection;
        $collection->setStrategy(new UriStrategy);

        $collection->get('/route/{id}/{name}', function ($id, $name) {
            $this->assertEquals('2', $id);
            $this->assertEquals('phil', $name);
        });

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route/2/phil');
    }

    /**
     * Assert that a route using the URI Strategy returns response when controller does
     *
     * @return void
     */
    public function testUriStrategyRouteReturnsResponseWhenControllerDoes()
    {
        $mockResponse = $this->getMock('Symfony\Component\HttpFoundation\Response');

        $collection = new Route\RouteCollection;
        $collection->setStrategy(new UriStrategy);

        $collection->get('/route/{id}/{name}', function ($id, $name) use ($mockResponse) {
            $this->assertEquals('2', $id);
            $this->assertEquals('phil', $name);
            return $mockResponse;
        });

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route/2/phil');

        $this->assertSame($mockResponse, $response);
    }

    /**
     * Assert that a route using the URI Strategy throws exception when Response
     * cannot be built
     *
     * @return void
     */
    public function testUriStrategyRouteThrowsExceptionWhenResponseCannotBeBuilt()
    {
        $this->setExpectedException('RuntimeException');

        $collection = new Route\RouteCollection;
        $collection->setStrategy(new UriStrategy);

        $collection->get('/route/{id}/{name}', function ($id, $name) {
            $this->assertEquals('2', $id);
            $this->assertEquals('phil', $name);
            return new \stdClass;
        });

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route/2/phil');
    }

    /**
     * Assert that a route using the Method Argument Strategy throws exception when Response
     * cannot be built
     *
     * @return void
     */
    public function testMethodArgumentStrategyRouteThrowsExceptionWhenResponseCannotBeBuilt()
    {
        $this->setExpectedException('RuntimeException');

        $collection = new Route\RouteCollection;
        $collection->setStrategy(new MethodArgumentStrategy);

        $collection->get('/route', function () {
            return new \stdClass;
        });

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');
    }

    /**
     * Asserts that the correct method is invoked on a class based controller
     *
     * @return void
     */
    public function testClassBasedControllerInvokesCorrectMethodOnMethodArgumentStrategy()
    {
        $controller = $this->getMock('SomeClass', ['someMethod']);

        $container = $this->getMock('League\Container\ContainerInterface');

        $container->expects($this->once())
                  ->method('get')
                  ->with($this->equalTo('SomeClass'))
                  ->will($this->returnValue($controller));

        $container->expects($this->once())
                  ->method('call')
                  ->with($this->equalTo([$controller, 'someMethod']))
                  ->will($this->returnValue('hello world'));

        $collection = new Route\RouteCollection($container);
        $collection->setStrategy(new MethodArgumentStrategy);

        $collection->get('/route', 'SomeClass::someMethod');
        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');

        $this->assertEquals('hello world', $response->getContent());
    }

    /**
     * Assert that a route using the Request -> Response Strategy gets passed the correct arguments
     *
     * @return void
     */
    public function testRequestResponseStrategyRouteReceivesCorrectArguments()
    {
        $collection = new Route\RouteCollection;
        $collection->setStrategy(new RequestResponseStrategy);

        $collection->get('/route', function ($request, $response) {
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Request', $request);
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
            return $response;
        });

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
    }

    /**
     * Assert that a route using the Request -> Response Strategy throws exception
     * when correct response not returned
     *
     * @return void
     */
    public function testRequestResponseStrategyRouteThrowsExceptionWhenWrongResponseReturned()
    {
        $this->setExpectedException('RuntimeException');

        $collection = new Route\RouteCollection;
        $collection->setStrategy(new RequestResponseStrategy);

        $collection->get('/route', function ($request, $response) {
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Request', $request);
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
            return [];
        });

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');
    }

    /**
     * Asserts that a 404 response is returned whilst using restful strategy
     *
     * @return void
     */
    public function testDispatcherHandles404CorrectlyOnRestfulStrategy()
    {
        $collection = new Route\RouteCollection;
        $collection->setStrategy(new RestfulStrategy);

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertSame('{"status_code":404,"message":"Not Found"}', $response->getContent());
        $this->assertSame(404, $response->getStatusCode());
    }

    /**
     * Asserts that a 404 exception is thrown whilst using standard strategies
     *
     * @return void
     */
    public function testDispatcherHandles404CorrectlyOnStandardStrategies()
    {
        $this->setExpectedException('League\Route\Http\Exception\NotFoundException', 'Not Found', 0);

        $collection = new Route\RouteCollection;
        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');
    }

    /**
     * Asserts that a 405 response is returned whilst using restful strategy
     *
     * @return void
     */
    public function testDispatcherHandles405CorrectlyOnRestfulStrategy()
    {
        $collection = new Route\RouteCollection;
        $collection->setStrategy(new RestfulStrategy);

        $collection->post('/route', 'handler');
        $collection->put('/route', 'handler');
        $collection->delete('/route', 'handler');

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertSame('{"status_code":405,"message":"Method Not Allowed"}', $response->getContent());
        $this->assertSame(405, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Allow'));
        $this->assertSame('POST, PUT, DELETE', $response->headers->get('Allow'));
    }

    /**
     * Asserts that a 405 exception is thrown whilst using standard strategies
     *
     * @return void
     */
    public function testDispatcherHandles405CorrectlyOnStandardStrategies()
    {
        $this->setExpectedException('League\Route\Http\Exception\MethodNotAllowedException', 'Method Not Allowed', 0);

        $collection = new Route\RouteCollection;

        $collection->post('/route', 'handler');
        $collection->put('/route', 'handler');
        $collection->delete('/route', 'handler');

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');
    }

    /**
     * Asserts that a custom strategy is dispatched correctly and the return of that
     * method bubbles out to the dispatcher
     *
     * @return void
     */
    public function testCustomStrategyIsDispatchedCorrectly()
    {
        $mockStrategy = $this->getMock('League\Route\Strategy\StrategyInterface');

        $mockStrategy->expects($this->once())
                     ->method('dispatch')
                     ->with($this->equalTo(['Controller', 'method']), $this->equalTo(['id' => 2, 'name' => 'phil']))
                     ->will($this->returnValue(['id' => 2, 'name' => 'phil']));

        $collection = new Route\RouteCollection;
        $collection->get('/route/{id}/{name}', 'Controller::method', $mockStrategy);

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route/2/phil');

        $this->assertSame(['id' => 2, 'name' => 'phil'], $response);
    }
}
