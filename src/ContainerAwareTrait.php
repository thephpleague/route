<?php declare(strict_types=1);

namespace League\Route;

use Psr\Container\ContainerInterface;

trait ContainerAwareTrait
{
    /**
     * @var \Psr\Container\ContainerInterface
     */
    protected $container;

    /**
     * Get container.
     *
     * @return \Psr\Container\ContainerInterface
     */
    public function getContainer() : ?ContainerInterface
    {
        return $this->container;
    }

    /**
     * Set container.
     *
     * @param \Psr\Container\ContainerInterface $container
     *
     * @return \League\Route\ContainerAwareInterface
     */
    public function setContainer(ContainerInterface $container) : ContainerAwareInterface
    {
        $this->container = $container;

        return $this;
    }
}
