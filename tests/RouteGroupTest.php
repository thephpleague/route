<?php

namespace League\Route\Test;

use League\Route\RouteGroup;

class RouteGroupTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that a route group is created and it registeres routes with collection.
     *
     * @return void
     */
    public function testGroupIsInvokedAndAddsRoutesToCollection()
    {
        $callback   = function () {};
        $collection = $this->getMock('League\Route\RouteCollectionInterface');
        $route      = $this->getMock('League\Route\Route');

        $route->expects($this->exactly(8))->method('setHost')->with($this->equalTo('example.com'))->will($this->returnSelf());
        $route->expects($this->exactly(8))->method('setScheme')->with($this->equalTo('https'))->will($this->returnSelf());
        $route->expects($this->exactly(8))->method('middleware')->with($this->equalTo($callback))->will($this->returnSelf());

        $collection->expects($this->at(0))->method('map')->with($this->equalTo('GET'), $this->equalTo('/acme/route'), $this->equalTo($callback))->will($this->returnValue($route));
        $collection->expects($this->at(1))->method('map')->with($this->equalTo('POST'), $this->equalTo('/acme/route'), $this->equalTo($callback))->will($this->returnValue($route));
        $collection->expects($this->at(2))->method('map')->with($this->equalTo('PUT'), $this->equalTo('/acme/route'), $this->equalTo($callback))->will($this->returnValue($route));
        $collection->expects($this->at(3))->method('map')->with($this->equalTo('PATCH'), $this->equalTo('/acme/route'), $this->equalTo($callback))->will($this->returnValue($route));
        $collection->expects($this->at(4))->method('map')->with($this->equalTo('DELETE'), $this->equalTo('/acme/route'), $this->equalTo($callback))->will($this->returnValue($route));
        $collection->expects($this->at(5))->method('map')->with($this->equalTo('OPTIONS'), $this->equalTo('/acme/route'), $this->equalTo($callback))->will($this->returnValue($route));
        $collection->expects($this->at(6))->method('map')->with($this->equalTo('HEAD'), $this->equalTo('/acme/route'), $this->equalTo($callback))->will($this->returnValue($route));

        $group = new RouteGroup('/acme', function ($route) use ($callback) {
            $route->get('/route', $callback)->setHost('example.com')->setScheme('https')->middleware($callback);
            $route->post('/route', $callback);
            $route->put('/route', $callback);
            $route->patch('/route', $callback);
            $route->delete('/route', $callback);
            $route->options('/route', $callback);
            $route->head('/route', $callback);
        }, $collection);

        $group
            ->setHost('example.com')
            ->setScheme('https')
            ->middleware($callback)
        ;

        $group();
    }
}
