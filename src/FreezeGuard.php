<?php

declare(strict_types=1);

namespace League\Route;

use LogicException;

final class FreezeGuard
{
    public static function assertNotFrozen(object $context): void
    {
        if ($context instanceof FreezeableInterface && $context->isFrozen()) {
            throw new LogicException('Cannot modify route after routes have been prepared');
        }
    }
}
