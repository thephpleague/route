<?php

namespace League\Route\Strategy;

interface StrategyAwareInterface
{
    /**
     * Set the strategy.
     *
     * @param \League\Route\Strategy\StrategyInterface $strategy
     *
     * @return $this
     */
    public function setStrategy(StrategyInterface $strategy);

    /**
     * Gets the strategy.
     *
     * @return \League\Route\Strategy\StrategyInterface
     */
    public function getStrategy();
}
