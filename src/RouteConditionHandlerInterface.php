<?php declare(strict_types=1);

namespace League\Route;

interface RouteConditionHandlerInterface
{
    /**
     * Get the host condition
     *
     * @return string|null
     */
    public function getHost(): ?string;

    /**
     * Set the host condition
     *
     * @param string $host
     *
     * @return static
     */
    public function setHost(string $host): RouteConditionHandlerInterface;

    /**
     * Get the route name
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Set the route name
     *
     * @param string $name
     *
     * @return static
     */
    public function setName(string $name): RouteConditionHandlerInterface;

    /**
     * Get the scheme condition
     *
     * @return string|null
     */
    public function getScheme(): ?string;

    /**
     * Set the scheme condition
     *
     * @param string $scheme
     *
     * @return static
     */
    public function setScheme(string $scheme): RouteConditionHandlerInterface;

    /**
     * Get the port condition
     *
     * @return int|null
     */
    public function getPort(): ?int;

    /**
     * Set the port condition
     *
     * @param int $port
     *
     * @return static
     */
    public function setPort(int $port): RouteConditionHandlerInterface;
}
