<?php

declare(strict_types=1);

namespace League\Route;

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
        return $this;
    }

    public function setName(string $name): RouteConditionHandlerInterface
    {
        $this->name = $name;
        return $this;
    }

    public function setPort(int $port): RouteConditionHandlerInterface
    {
        $this->port = $port;
        return $this;
    }

    public function setScheme(string $scheme): RouteConditionHandlerInterface
    {
        $this->scheme = $scheme;
        return $this;
    }
}
