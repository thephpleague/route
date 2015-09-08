<?php

namespace League\Route;

use League\Container\ImmutableContainerAwareInterface;
use League\Container\ImmutableContainerAwareTrait;
use League\Route\Strategy\StrategyTrait;

class Route implements ImmutableContainerAwareInterface
{
    use ImmutableContainerAwareTrait;
    use StrategyTrait;

    /**
     * @var string|callable
     */
    protected $callable;

    /**
     * @var string
     */
    protected $domain;

    /**
     * @var string[]
     */
    protected $methods = [];

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $scheme;

    /**
     * Dispatch the route via the attached strategy.
     *
     * @param  array $vars
     * @return mixed
     */
    public function dispatch(array $vars)
    {
        $callable = $this->getCallable();

        if (is_string($callable) && strpos($callable, '::') !== false) {
            $callable = explode('::', $callable);
        }

        if (is_array($callable) && isset($callable[0])) {
            $callable = [
                (is_object($callable[0])) ? $callable[0] : $this->getContainer()->get($callable[0]),
                $callable[1]
            ]
        }

        return $this->getStrategy()->dispatch($callable, $vars);
    }

    /**
     * Get the callable.
     *
     * @return string|callable
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * Set the callable.
     *
     * @param  string|callable $callable
     * @return \League\Route\Route
     */
    public function setCallable($callable)
    {
        $this->callable = $callable;

        return $this;
    }

    /**
     * Get the domain.
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set the domain.
     *
     * @param  string $domain
     * @return \League\Route\Route
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

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
     * @param  string[] $methods
     * @return \League\Route\Route
     */
    public function setMethods(array $methods)
    {
        $this->methods = $methods;

        return $this;
    }

    /**
     * Get the name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name.
     *
     * @param  string name
     * @return \League\Route\Route
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the path.
     *
     * @return string
     */
    public function getPath()
    {
        return $path;
    }

    /**
     * Set the path.
     *
     * @param  string $path
     * @return \League\Route\Route
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get the scheme.
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Set the scheme.
     *
     * @param  string $scheme
     * @return \League\Route\Route
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;

        return $this;
    }
}
