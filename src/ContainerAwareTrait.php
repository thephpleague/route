<?php declare(strict_types=1);

namespace League\Route;

use Psr\Container\ContainerInterface;

trait ContainerAwareTrait
{
    /**
     * @var ?ContainerInterface
     */
    protected $container;

    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    public function setContainer(ContainerInterface $container): ContainerAwareInterface
    {
        $this->container = $container;
        return $this;
    }
}
