<?php

declare(strict_types=1);

namespace League\Route;

use RuntimeException;

trait RouteConditionHandlerTrait
{
    /**
     * @var ?string
     */
    protected $host;

    /**
     * @var ?string
     */
    protected $name;

    /**
     * @var ?int
     */
    protected $port;

    /**
     * @var ?string
     */
    protected $scheme;

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getScheme(): ?string
    {
        return $this->scheme;
    }

    public function setHost(string $host): RouteConditionHandlerInterface
    {
        $this->host = $host;
        return $this->checkAndReturnSelf();
    }

    public function setName(string $name): RouteConditionHandlerInterface
    {
        $this->name = $name;
        return $this->checkAndReturnSelf();
    }

    public function setPort(int $port): RouteConditionHandlerInterface
    {
        $this->port = $port;
        return $this->checkAndReturnSelf();
    }

    public function setScheme(string $scheme): RouteConditionHandlerInterface
    {
        $this->scheme = $scheme;
        return $this->checkAndReturnSelf();
    }

    private function checkAndReturnSelf(): RouteConditionHandlerInterface
    {
        if ($this instanceof RouteConditionHandlerInterface) {
            return $this;
        }

        throw new RuntimeException(sprintf(
            'Trait (%s) must be consumed by an instance of (%s)',
            __TRAIT__,
            RouteConditionHandlerInterface::class
        ));
    }
}
