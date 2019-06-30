<?php declare(strict_types=1);

namespace League\Route;

use Psr\Container\ContainerInterface;

interface ContainerAwareInterface
{
    /**
     * Get the current container
     *
     * @return ContainerInterface|null
     */
    public function getContainer(): ?ContainerInterface;

    /**
     * Set the container implementation
     *
     * @param ContainerInterface $container
     *
     * @return static
     */
    public function setContainer(ContainerInterface $container): ContainerAwareInterface;
}
