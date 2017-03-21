<?php

namespace League\Route;

use Psr\Container\ContainerInterface;

interface ContainerAwareInterface
{
    /**
     * Get container.
     *
     * @return \Psr\Container\ContainerInterface
     */
    public function getContainer();

    /**
     * Set container.
     *
     * @param \Psr\Container\ContainerInterface $container
     *
     * @return self
     */
    public function setContainer(ContainerInterface $container);
}
