<?php declare(strict_types=1);

namespace League\Route;

use Psr\Container\ContainerInterface;

interface ContainerAwareInterface
{
    /**
     * Get the current container
     *
     * @return \Psr\Container\ContainerInterface|null
     */
    public function getContainer() : ?ContainerInterface;

    /**
     * Set the container implementation
     *
     * @param \Psr\Container\ContainerInterface $container
     *
     * @return static
     */
    public function setContainer(ContainerInterface $container) : ContainerAwareInterface;
}
