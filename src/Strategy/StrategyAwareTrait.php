<?php

namespace League\Route\Strategy;

trait StrategyAwareTrait
{
    /**
     * @var \League\Route\Strategy\StrategyInterface
     */
    protected $strategy;

    /**
     * Set the strategy.
     *
     * @param \League\Route\Strategy\StrategyInterface $strategy
     *
     * @return $this
     */
    public function setStrategy(StrategyInterface $strategy)
    {
        $this->strategy = $strategy;

        return $this;
    }

    /**
     * Gets the strategy.
     *
     * @return \League\Route\Strategy\StrategyInterface
     */
    public function getStrategy()
    {
        return $this->strategy;
    }
}
