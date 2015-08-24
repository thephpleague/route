<?php

namespace League\Route\Strategy;

trait StrategyTrait
{
    /**
     * @var \League\Route\Strategy\StrategyInterface
     */
    protected $strategy;

    /**
     * Tells the implementor which strategy to use, this should override any higher
     * level setting of strategies, such as on specific routes
     *
     * @param  \League\Route\Strategy\StrategyInterface $strategy
     */
    public function setStrategy(StrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    /**
     * Gets global strategy
     *
     * @return \League\Route\Strategy\StrategyInterface
     */
    public function getStrategy()
    {
        return $this->strategy;
    }
}
