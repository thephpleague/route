<?php

namespace League\Route;

trait RouteConditionTrait
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
     * Get the host.
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set the host.
     *
     * @param string $host
     *
     * @return \League\Route\Route
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Get the name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name.
     *
     * @param string $name
     *
     * @return \League\Route\Route
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the scheme.
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Set the scheme.
     *
     * @param string $scheme
     *
     * @return \League\Route\Route
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;

        return $this;
    }
}
