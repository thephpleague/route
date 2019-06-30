<?php declare(strict_types=1);

namespace League\Route;

trait RouteConditionHandlerTrait
{
    /**
     * @var string|null
     */
    protected $host;

    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $scheme;

    /**
     * @var int|null
     */
    protected $port;

    /**
     * {@inheritdoc}
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * {@inheritdoc}
     */
    public function setHost(string $host): RouteConditionHandlerInterface
    {
        $this->host = $host;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName(string $name): RouteConditionHandlerInterface
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getScheme(): ?string
    {
        return $this->scheme;
    }

    /**
     * {@inheritdoc}
     */
    public function setScheme(string $scheme): RouteConditionHandlerInterface
    {
        $this->scheme = $scheme;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * {@inheritdoc}
     */
    public function setPort(int $port): RouteConditionHandlerInterface
    {
        $this->port = $port;

        return $this;
    }
}
