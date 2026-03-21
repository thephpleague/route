<?php

declare(strict_types=1);

namespace League\Route\Strategy;

use League\Route\FreezeGuard;

trait StrategyAwareTrait
{
    protected ?StrategyInterface $strategy = null;

    public function setStrategy(StrategyInterface $strategy): StrategyAwareInterface
    {
        FreezeGuard::assertNotFrozen($this);
        $this->strategy = $strategy;
        return $this;
    }

    public function getStrategy(): ?StrategyInterface
    {
        return $this->strategy;
    }
}
