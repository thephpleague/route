<?php

declare(strict_types=1);

namespace League\Route;

use Closure;
use League\Route\Middleware\{MiddlewareAwareInterface, MiddlewareAwareTrait};
use League\Route\Strategy\{StrategyAwareInterface, StrategyAwareTrait, StrategyInterface};
use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
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

    /** @var Closure|array<int, mixed>|string|object */
    protected mixed $handler;

    /** @var array<string> */
    protected array $defaultVars = [];

    /** @var array<string, string> */
    protected array $pathVars = [];

    /**
     * @param array<string>|string $method
     * @param callable|array<string>|string|RequestHandlerInterface $handler
     * @param array<string> $vars
     */
    public function __construct(
        protected array|string $method,
        protected string $path,
        callable|array|string|RequestHandlerInterface $handler,
        protected ?RouteGroup $group = null,
        array $vars = [],
    ) {
        $this->defaultVars = $vars;
        $this->handler = ($handler instanceof RequestHandlerInterface) ? [$handler, 'handle'] : $handler;
    }

    public function getCallable(?ContainerInterface $container = null): callable
    {
        $callable = $this->handler;

        if (is_string($callable) && str_contains($callable, '::')) {
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

        if ($callable instanceof RequestHandlerInterface) {
            $callable = [$callable, 'handle'];
        }

        if (!is_callable($callable)) {
            throw new RuntimeException('Could not resolve a callable for this route');
        }

        return $callable;
    }

    /** @return array<string>|string */
    public function getMethod(): array|string
    {
        return $this->method;
    }

    public function getParentGroup(): ?RouteGroup
    {
        return $this->group;
    }

    /** @param array<string, string> $replacements */
    public function getPath(array $replacements = []): string
    {
        $toReplace = [];

        foreach ($replacements as $wildcard => $actual) {
            $toReplace['/{' . preg_quote($wildcard, '/') . '(:.*?)?}/'] = $actual;
        }

        return preg_replace(array_keys($toReplace), array_values($toReplace), $this->path);
    }

    /** @return array<string, string> */
    public function getVars(): array
    {
        return array_merge($this->defaultVars, $this->pathVars);
    }

    #[Override]
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
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
        $prefix = $this->group->getPrefix();
        $path = $this->getPath();

        if (strcmp($prefix, substr($path, 0, strlen($prefix))) !== 0) {
            $path = $prefix . $path;
            $this->path = $path;
        }

        return $this;
    }

    /** @param array<string> $vars */
    public function setVars(array $vars): self
    {
        $this->defaultVars = $vars;
        return $this;
    }

    /** @param array<string, string> $pathVars */
    public function setPathVars(array $pathVars): self
    {
        $this->pathVars = $pathVars;
        return $this;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function resolve(string $class, ?ContainerInterface $container = null): mixed
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
