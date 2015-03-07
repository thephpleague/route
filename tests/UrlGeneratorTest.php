<?php

namespace League\Route\Test;

use League\Route\RouteCollection;
use League\Route\UrlGenerator;

class UrlGeneratorTest extends \PHPUnit_Framework_TestCase
{
    
    /**
     * Asserts that the @ sign is correctly added when missing
     */
    public function testMissingAtSign()
    {
        $routes = new RouteCollection;
        $routes->addRoute('GET', '@namedRoute/test', function() {
        });
        $generator = new UrlGenerator($routes);
        
        $this->assertEquals($generator->generate('@namedRoute'), $generator->generate('namedRoute'));
    }
    
    /**
     * Asserts that named routes are correctly generated
     */
    public function testNamedRouteWithoutParams()
    {
        $routes = new RouteCollection;
        $routes->addRoute('GET', '@namedRoute/test', function() {
        });
        $routes->addRoute('GET', '@namedSlashedRoute/test/', function() {
        });
        $generator = new UrlGenerator($routes);
        
        $this->assertEquals('test', $generator->generate('@namedRoute'));
        $this->assertEquals('test/', $generator->generate('@namedSlashedRoute'));
    }
    
    /**
     * Asserts that the base url work correctly
     */
    public function testSetBaseUrl()
    {
        $routes = new RouteCollection;
        $routes->addRoute('GET', '@namedRoute/test', function() {
        });
        $generator = new UrlGenerator($routes);
        $generator->setBaseUrl('http://myfakedomain.com/');
        
        $this->assertEquals('http://myfakedomain.com/test', $generator->generate('@namedRoute'));
        
        $generator->setBaseUrl('http://myfakedomain.com');
        $this->assertEquals('http://myfakedomain.com/test', $generator->generate('@namedRoute'));
    }
    
    /**
     * Asserts that named routes with parameters are correctly generated
     */
    public function testSimpleParameters()
    {
        $routes = new RouteCollection;
        $routes->addRoute('GET', '@numberRoute/test/{id:number}', function() {
        });
        $routes->addRoute('GET', '@wordRoute/test/{name:word}', function() {
        });
        $routes->addRoute('GET', '@urlRoute/test/{url:alphanum_dash}', function() {
        });
        $routes->addRoute('GET', '@paramRoute/test/{param}', function() {
        });
        $routes->addRoute('GET', '@manyRoute/test/{id:number}/{name:word}', function() {
        });
        $generator = new UrlGenerator($routes);
        
        $this->assertEquals('test/1', $generator->generate('@numberRoute', array('id' => 1)));
        $this->assertEquals('test/Name', $generator->generate('@wordRoute', array('name' => 'Name')));
        $this->assertEquals('test/My-Url_2', $generator->generate('@urlRoute', array('url' => 'My-Url_2')));
        $this->assertEquals('test/$%?%$', $generator->generate('@paramRoute', array('param' => '$%?%$')));
        $this->assertEquals('test/10/test', $generator->generate('@manyRoute', array('id' => 10, 'name' => 'test')));
    }
    
    /**
     * Asserts that an exception is thrown when a parameter is missing
     *
     * @expectedException InvalidArgumentException
     */
    public function testRouteWithMissingParameter()
    {
        $routes = new RouteCollection;
        $routes->addRoute('GET', '@namedRoute/test/{id:number}', function() {
        });
        $generator = new UrlGenerator($routes);
        
        $generator->generate('@namedRoute');
    }
    
    /**
     * Asserts that an exception is thrown when a parameter is invalid
     *
     * @expectedException InvalidArgumentException
     */
    public function testRouteWithInvalidParameter()
    {
        $routes = new RouteCollection;
        $routes->addRoute('GET', '@namedRoute/test/{id:number}', function() {
        });
        $generator = new UrlGenerator($routes);
        
        $generator->generate('@namedRoute', array('id' => 'InvalidID'));
        
    }
}
