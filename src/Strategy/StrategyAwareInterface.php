<?php declare(strict_types=1);

namespace League\Route\Strategy;

interface StrategyAwareInterface
{
    /**
     * Set the strategy.
     *
     * @param \League\Route\Strategy\StrategyInterface $strategy
     *
     * @return \League\Route\Strategy\StrategyAwareInterface
     */
    public function setStrategy(StrategyInterface $strategy) : StrategyAwareInterface;

    /**
     * Gets the strategy.
     *
     * @return \League\Route\Strategy\StrategyInterface
     */
    public function getStrategy() : ?StrategyInterface;
}
