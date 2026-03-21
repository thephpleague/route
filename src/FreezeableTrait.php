<?php

declare(strict_types=1);

namespace League\Route;

use LogicException;

trait FreezeableTrait
{
    protected bool $frozen = false;

    public function freeze(): void
    {
        $this->frozen = true;
    }

    public function isFrozen(): bool
    {
        return $this->frozen;
    }

    protected function assertNotFrozen(): void
    {
        if ($this->frozen) {
            throw new LogicException('Cannot modify route after routes have been prepared');
        }
    }
}
