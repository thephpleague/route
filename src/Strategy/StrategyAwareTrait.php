<?php declare(strict_types=1);

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
     * @return \League\Route\Strategy\StrategyAwareInterface
     */
    public function setStrategy(StrategyInterface $strategy) : StrategyAwareInterface
    {
        $this->strategy = $strategy;

        return $this;
    }

    /**
     * Gets the strategy.
     *
     * @return \League\Route\Strategy\StrategyInterface
     */
    public function getStrategy() : ?StrategyInterface
    {
        return $this->strategy;
    }
}
