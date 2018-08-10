<?php declare(strict_types=1);

namespace League\Route;

use InvalidArgumentException;
use League\Route\Middleware\{MiddlewareAwareInterface, MiddlewareAwareTrait};
use League\Route\Strategy\{StrategyAwareInterface, StrategyAwareTrait};
use Psr\Container\ContainerInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class Route implements
    MiddlewareInterface,
    MiddlewareAwareInterface,
    RouteConditionHandlerInterface,
    StrategyAwareInterface
{
    use MiddlewareAwareTrait;
    use RouteConditionHandlerTrait;
    use StrategyAwareTrait;

    /**
     * @var callable|string
     */
    protected $handler;

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

    public function __construct(string $method, string $path, $handler)
    {
        $this->method  = $method;
        $this->path    = $path;
        $this->handler = $handler;
    }

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
    public function getCallable(?ContainerInterface $container = null) : callable
    {
        $callable = $this->handler;

        if (is_string($callable) && strpos($callable, '::') !== false) {
            $callable = explode('::', $callable);
        }

        if (is_array($callable) && isset($callable[0]) && is_object($callable[0])) {
            $callable = [$callable[0], $callable[1]];
        }

        if (is_array($callable) && isset($callable[0]) && is_string($callable[0])) {
            $class = (! is_null($container) && $container->has($callable[0]))
                ? $container->get($callable[0])
                : new $callable[0]
            ;

            $callable = [$class, $callable[1]];
        }

        if (is_string($callable) && method_exists($callable, '__invoke')) {
            $callable = (! is_null($container) && $container->has($callable))
                ? $container->get($callable)
                : new $callable
            ;
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
     * Get the parent group.
     *
     * @return \League\Route\RouteGroup
     */
    public function getParentGroup() : ?RouteGroup
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
     * Get the methods.
     *
     * @return string
     */
    public function getMethod() : string
    {
        return $this->method;
    }
}
