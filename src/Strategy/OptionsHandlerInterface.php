<?php

declare(strict_types=1);

namespace League\Route\Strategy;

interface OptionsHandlerInterface
{
    /**
     * @param array<string> $methods
     */
    public function getOptionsCallable(array $methods): callable;
}
