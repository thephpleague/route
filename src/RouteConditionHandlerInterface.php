<?php declare(strict_types=1);

namespace League\Route;

interface RouteConditionHandlerInterface
{
    /**
     * Get the host.
     *
     * @return string
     */
    public function getHost() : ?string;

    /**
     * Set the host.
     *
     * @param string $host
     *
     * @return \League\Route\RouteConditionHandlerInterface
     */
    public function setHost(string $host) : RouteConditionHandlerInterface;

    /**
     * Get the name.
     *
     * @return string
     */
    public function getName() : ?string;

    /**
     * Set the name.
     *
     * @param string $name
     *
     * @return \League\Route\RouteConditionHandlerInterface
     */
    public function setName(string $name) : RouteConditionHandlerInterface;

    /**
     * Get the scheme.
     *
     * @return string
     */
    public function getScheme() : ?string;

    /**
     * Set the scheme.
     *
     * @param string $scheme
     *
     * @return \League\Route\RouteConditionHandlerInterface
     */
    public function setScheme(string $scheme) : RouteConditionHandlerInterface;

    /**
     * Get the port.
     *
     * @return int
     */
    public function getPort() : ?int;

    /**
     * Set the port.
     *
     * @param int $port
     *
     * @return \League\Route\RouteConditionHandlerInterface
     */
    public function setPort(int $port) : RouteConditionHandlerInterface;
}
