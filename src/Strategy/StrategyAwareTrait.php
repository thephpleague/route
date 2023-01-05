<?php

declare(strict_types=1);

namespace League\Route\Strategy;

trait StrategyAwareTrait
{
    protected ?StrategyInterface $strategy = null;

    public function setStrategy(StrategyInterface $strategy): StrategyAwareInterface
    {
        $this->strategy = $strategy;
        return $this;
    }

    public function getStrategy(): ?StrategyInterface
    {
        return $this->strategy;
    }
}
