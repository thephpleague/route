<?php declare(strict_types=1);

namespace League\Route;

use League\Route\Middleware\{MiddlewareAwareInterface, MiddlewareAwareTrait};
use League\Route\Strategy\{StrategyAwareInterface, StrategyAwareTrait};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class Route implements
    MiddlewareInterface,
    MiddlewareAwareInterface,
    StrategyAwareInterface
{
    use MiddlewareAwareTrait;
    use RouteConditionTrait;
    use StrategyAwareTrait;

    /**
     * @var callable
     */
    protected $callable;

    /**
     * @var \League\Route\RouteGroup
     */
    protected $group;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var array
     */
    protected $vars = [];

    /**
     * {@inheritdoc}
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $requestHandler
    ) : ResponseInterface {
        return $this->getStrategy()->invokeRouteCallable($this, $request);
    }

    /**
     * Get the callable.
     *
     * @param \Psr\Container\ContainerInterface $container
     *
     * @throws \RuntimeException
     *
     * @return callable
     */
    public function getCallable(ContainerInterface $container = null) : callable
    {
        $callable = $this->callable;

        if (is_string($callable) && strpos($callable, '::') !== false) {
            $callable = explode('::', $callable);
        }

        if (is_array($callable) && isset($callable[0]) && is_object($callable[0])) {
            $callable = [$callable[0], $callable[1]];
        }

        if (is_array($callable) && isset($callable[0]) && is_string($callable[0])) {
            $class = (! is_null($container) && $container->has($callable[0]))
                ? $container->get($callable[0])
                : new $callable[0];

            $callable = [$class, $callable[1]];
        }

        if (! is_callable($callable)) {
            throw new InvalidArgumentException('Could not resolve a callable for this route');
        }

        return $callable;
    }

    /**
     * Return vars to be passed to route callable.
     *
     * @return array
     */
    public function getVars() : array
    {
        return $this->vars;
    }

    /**
     * Set vars to be passed to route callable.
     *
     * @param array $vars
     *
     * @return \League\Route\Route
     */
    public function setVars(array $vars) : self
    {
        $this->vars = $vars;

        return $this;
    }

    /**
     * Set the callable.
     *
     * @param callable|string $callable
     *
     * @return \League\Route\Route
     */
    public function setCallable($callable) : self
    {
        $this->callable = $callable;

        return $this;
    }

    /**
     * Get the parent group.
     *
     * @return \League\Route\RouteGroup|null
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
    public function setParentGroup(RouteGroup $group) : self
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get the path.
     *
     * @return string
     */
    public function getPath() : string
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
    public function setPath($path) : self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get the methods.
     *
     * @return string
     */
    public function getMethod() : string
    {
        return $this->method;
    }

    /**
     * Get the method.
     *
     * @param string $method
     *
     * @return \League\Route\Route
     */
    public function setMethod(string $method) : self
    {
        $this->method = $method;

        return $this;
    }
}
