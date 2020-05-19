<?php

declare(strict_types=1);

namespace League\Route\Strategy;

interface OptionsHandlerInterface
{
    public function getOptionsCallable(array $methods): callable;
}
