<?php declare(strict_types=1);

namespace League\Route;

use Psr\Container\ContainerInterface;

interface ContainerAwareInterface
{
    /**
     * Get container.
     *
     * @return \Psr\Container\ContainerInterface|null
     */
    public function getContainer();

    /**
     * Set container.
     *
     * @param \Psr\Container\ContainerInterface $container
     *
     * @return self
     */
    public function setContainer(ContainerInterface $container) : ContainerAwareInterface;
}
