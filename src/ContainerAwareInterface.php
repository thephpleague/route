<?php declare(strict_types=1);

namespace League\Route;

use Psr\Container\ContainerInterface;

interface ContainerAwareInterface
{
    /**
     * Get container.
     *
     * @return \Psr\Container\ContainerInterface
     */
    public function getContainer() : ?ContainerInterface;

    /**
     * Set container.
     *
     * @param \Psr\Container\ContainerInterface $container
     *
     * @return \League\Route\ContainerAwareInterface
     */
    public function setContainer(ContainerInterface $container) : ContainerAwareInterface;
}
