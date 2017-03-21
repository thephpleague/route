<?php

namespace League\Route;

use InvalidArgumentException;
use League\Route\ContainerAwareInterface;
use League\Route\ContainerAwareTrait;
use League\Route\Http\RequestAwareInterface;
use League\Route\Http\ResponseAwareInterface;
use League\Route\Middleware\ExecutionChain;
use League\Route\Middleware\StackAwareInterface as MiddlewareAwareInterface;
use League\Route\Middleware\StackAwareTrait as MiddlewareAwareTrait;
use League\Route\Strategy\StrategyAwareInterface;
use League\Route\Strategy\StrategyAwareTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Route implements ContainerAwareInterface, MiddlewareAwareInterface, StrategyAwareInterface
{
    use ContainerAwareTrait;
    use MiddlewareAwareTrait;
    use RouteConditionTrait;
    use StrategyAwareTrait;

    /**
     * @var string|callable
     */
    protected $callable;

    /**
     * @var \League\Route\RouteGroup
     */
    protected $group;

    /**
     * @var string[]
     */
    protected $methods = [];

    /**
     * @var string
     */
    protected $path;

    /**
     * Dispatch the route via the attached strategy.
     *
     * @param array $vars
     *
     * @return \League\Route\Middleware\ExecutionChain
     */
    public function getExecutionChain(array $vars)
    {
        $callable = $this->getStrategy()->getCallable($this, $vars);

        $execChain = (new ExecutionChain)->middleware($callable);

        foreach ($this->getMiddlewareStack() as $middleware) {
            $execChain->middleware($middleware);
        }

        return $execChain;
    }

    /**
     * Get the callable.
     *
     * @throws \RuntimeException
     *
     * @return callable
     */
    public function getCallable()
    {
        $callable = $this->callable;

        if (is_string($callable) && strpos($callable, '::') !== false) {
            $callable = explode('::', $callable);
        }

        if (is_array($callable) && isset($callable[0]) && is_object($callable[0])) {
            $callable = [$callable[0], $callable[1]];
        }

        if (is_array($callable) && isset($callable[0]) && is_string($callable[0])) {
            $class = ($this->getContainer()->has($callable[0]))
                   ? $this->getContainer()->get($callable[0])
                   : new $callable[0];

            $callable = [$class, $callable[1]];
        }

        if (! is_callable($callable)) {
            throw new InvalidArgumentException('Could not resolve a callable for this route');
        }

        return $callable;
    }

    /**
     * Set the callable.
     *
     * @param string|callable $callable
     *
     * @return \League\Route\Route
     */
    public function setCallable($callable)
    {
        $this->callable = $callable;

        return $this;
    }

    /**
     * Get the parent group.
     *
     * @return \League\Route\RouteGroup
     */
    public function getParentGroup()
    {
        return $this->group;
    }

    /**
     * Set the parent group.
     *
     * @param \League\Route\RouteGroup $group
     *
     * @return \League\Route\Route
     */
    public function setParentGroup(RouteGroup $group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get the path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set the path.
     *
     * @param string $path
     *
     * @return \League\Route\Route
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get the methods.
     *
     * @return string[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Get the methods.
     *
     * @param string[] $methods
     *
     * @return \League\Route\Route
     */
    public function setMethods(array $methods)
    {
        $this->methods = $methods;

        return $this;
    }
}
