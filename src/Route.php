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
     * @var RouteGroup
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
     * Construct.
     *
     * @param string          $method
     * @param string          $path
     * @param callable|string $handler
     */
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
    ): ResponseInterface {
        return $this->getStrategy()->invokeRouteCallable($this, $request);
    }

    /**
     * Get the controller callable
     *
     * @param ContainerInterface|null $container
     *
     * @return callable
     *
     * @throws InvalidArgumentException
     */
    public function getCallable(?ContainerInterface $container = null): callable
    {
        $callable = $this->handler;

        if (is_string($callable) && strpos($callable, '::') !== false) {
            $callable = explode('::', $callable);
        }

        if (is_array($callable) && isset($callable[0]) && is_object($callable[0])) {
            $callable = [$callable[0], $callable[1]];
        }

        if (is_array($callable) && isset($callable[0]) && is_string($callable[0])) {
            $callable = [$this->resolveClass($container, $callable[0]), $callable[1]];
        }

        if (is_string($callable) && method_exists($callable, '__invoke')) {
            $callable = $this->resolveClass($container, $callable);
        }

        if (! is_callable($callable)) {
            throw new InvalidArgumentException('Could not resolve a callable for this route');
        }

        return $callable;
    }

    /**
     * Get an object instance from a class name
     *
     * @param ContainerInterface|null $container
     * @param string                  $class
     *
     * @return object
     */
    protected function resolveClass(?ContainerInterface $container = null, string $class)
    {
        if ($container instanceof ContainerInterface && $container->has($class)) {
            return $container->get($class);
        }

        return new $class();
    }

    /**
     * Return variables to be passed to route callable
     *
     * @return array
     */
    public function getVars(): array
    {
        return $this->vars;
    }

    /**
     * Set variables to be passed to route callable
     *
     * @param array $vars
     *
     * @return Route
     */
    public function setVars(array $vars): self
    {
        $this->vars = $vars;

        return $this;
    }

    /**
     * Get the parent group
     *
     * @return RouteGroup
     */
    public function getParentGroup(): ?RouteGroup
    {
        return $this->group;
    }

    /**
     * Set the parent group
     *
     * @param RouteGroup $group
     *
     * @return Route
     */
    public function setParentGroup(RouteGroup $group): self
    {
        $this->group = $group;
        $prefix      = $this->group->getPrefix();
        $path        = $this->getPath();

        if (strcmp($prefix, substr($path, 0, strlen($prefix))) !== 0) {
            $path = $prefix . $path;
            $this->path = $path;
        }

        return $this;
    }

    /**
     * Get the path
     *
     * @param array $replacements
     *
     * @return string
     */
    public function getPath(array $replacements = []): string
    {
        $toReplace = [];

        foreach ($replacements as $wildcard => $actual) {
            $toReplace['/{' . preg_quote($wildcard, '/') . '(:.*?)?}/'] = $actual;
        }

        return preg_replace(array_keys($toReplace), array_values($toReplace), $this->path);
    }

    /**
     * Get the HTTP method
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }
}
