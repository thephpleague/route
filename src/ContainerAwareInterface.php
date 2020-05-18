<?php

declare(strict_types=1);

namespace League\Route;

use Psr\Container\ContainerInterface;

interface ContainerAwareInterface
{
    public function getContainer(): ?ContainerInterface;
    public function setContainer(ContainerInterface $container): ContainerAwareInterface;
}
