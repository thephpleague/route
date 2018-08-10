<?php declare(strict_types=1);

namespace League\Route;

trait RouteConditionHandlerTrait
{
    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $scheme;

    /**
     * @var int
     */
    protected $port;

    /**
     * Get the host.
     *
     * @return string
     */
    public function getHost() : ?string
    {
        return $this->host;
    }

    /**
     * Set the host.
     *
     * @param string $host
     *
     * @return \League\Route\RouteConditionHandlerInterface
     */
    public function setHost(string $host) : RouteConditionHandlerInterface
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Get the name.
     *
     * @return string
     */
    public function getName() : ?string
    {
        return $this->name;
    }

    /**
     * Set the name.
     *
     * @param string $name
     *
     * @return \League\Route\RouteConditionHandlerInterface
     */
    public function setName(string $name) : RouteConditionHandlerInterface
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the scheme.
     *
     * @return string
     */
    public function getScheme() : ?string
    {
        return $this->scheme;
    }

    /**
     * Set the scheme.
     *
     * @param string $scheme
     *
     * @return \League\Route\RouteConditionHandlerInterface
     */
    public function setScheme(string $scheme) : RouteConditionHandlerInterface
    {
        $this->scheme = $scheme;

        return $this;
    }

    /**
     * Get the port.
     *
     * @return int
     */
    public function getPort() : ?int
    {
        return $this->port;
    }

    /**
     * Set the port.
     *
     * @param int $port
     *
     * @return \League\Route\RouteConditionHandlerInterface
     */
    public function setPort(int $port) : RouteConditionHandlerInterface
    {
        $this->port = $port;

        return $this;
    }
}
