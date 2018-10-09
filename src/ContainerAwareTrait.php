<?php declare(strict_types=1);

namespace League\Route;

use Psr\Container\ContainerInterface;

trait ContainerAwareTrait
{
    /**
     * @var \Psr\Container\ContainerInterface|null
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function getContainer() : ?ContainerInterface
    {
        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container) : ContainerAwareInterface
    {
        $this->container = $container;

        return $this;
    }
}
