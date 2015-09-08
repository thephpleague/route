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
     * @return $this
     */
    public function setStrategy(StrategyInterface $strategy)
    {
        $this->strategy = $strategy;

        return $this;
    }

    /**
     * Gets global strategy
     *
     * @return integer
     */
    public function getStrategy()
    {
        return $this->strategy;
    }
}
