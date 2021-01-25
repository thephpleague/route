<?php

declare(strict_types=1);

namespace League\Route;

use League\Route\Middleware\{MiddlewareAwareInterface, MiddlewareAwareTrait};
use League\Route\Strategy\{StrategyAwareInterface, StrategyAwareTrait, StrategyInterface};
use Psr\Container\ContainerInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use RuntimeException;

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

    public function __construct(string $method, string $path, $handler)
    {
        $this->method  = $method;
        $this->path    = $path;
        $this->handler = $handler;
    }

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
            $callable = [$this->resolve($callable[0], $container), $callable[1]];
        }

        if (is_string($callable)) {
            $callable = $this->resolve($callable, $container);
        }

        if (!is_callable($callable)) {
            throw new RuntimeException('Could not resolve a callable for this route');
        }

        return $callable;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getParentGroup(): ?RouteGroup
    {
        return $this->group;
    }

    public function getPath(array $replacements = []): string
    {
        $toReplace = [];

        foreach ($replacements as $wildcard => $actual) {
            $toReplace['/{' . preg_quote($wildcard, '/') . '(:.*?)?}/'] = $actual;
        }

        return preg_replace(array_keys($toReplace), array_values($toReplace), $this->path);
    }

    public function getVars(): array
    {
        return $this->vars;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $strategy = $this->getStrategy();

        if (!($strategy instanceof StrategyInterface)) {
            throw new RuntimeException('A strategy must be set to process a route');
        }

        return $strategy->invokeRouteCallable($this, $request);
    }

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

    public function setVars(array $vars): self
    {
        $this->vars = $vars;
        return $this;
    }

    protected function resolve(string $class, ?ContainerInterface $container = null)
    {
        if ($container instanceof ContainerInterface && $container->has($class)) {
            return $container->get($class);
        }

        if (class_exists($class)) {
            return new $class();
        }

        return $class;
    }
}
