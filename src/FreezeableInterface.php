<?php

declare(strict_types=1);

namespace League\Route;

interface FreezeableInterface
{
    public function freeze(): void;
    public function isFrozen(): bool;
}
