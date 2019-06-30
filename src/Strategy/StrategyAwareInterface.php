<?php declare(strict_types=1);

namespace League\Route\Strategy;

interface StrategyAwareInterface
{
    /**
     * Get the current strategy
     *
     * @return StrategyInterface
     */
    public function getStrategy(): ?StrategyInterface;

    /**
     * Set the strategy implementation
     *
     * @param StrategyInterface $strategy
     *
     * @return static
     */
    public function setStrategy(StrategyInterface $strategy): StrategyAwareInterface;
}
