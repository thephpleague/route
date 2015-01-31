<?php

namespace League\Route\Strategy;

use Closure;

class MethodArgumentStrategy extends AbstractStrategy implements StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function dispatch($controller, array $vars)
    {
        $reflection = $this->getReflection($controller);

        $invokeArguments = [];
        foreach ($reflection->getParameters() as $param) {
            if ($param->getClass() instanceof \ReflectionClass) {
                $invokeArguments[] = $this->getContainer()->get($param->getClass()->getName());
            }
        }

        array_push($invokeArguments, $vars);
        $response = $this->invokeController($controller, $invokeArguments);
        

        return $this->determineResponse($response);
    }

    /**
     * @param   mixed $controller
     * @return  ReflectionFunctionAbstract
     */
    protected function getReflection($controller)
    {
        return ($controller instanceof Closure) ? $this->getReflectionFunction($controller) : $this->getReflectionClass($controller[0])->getMethod($controller[1]);
    }

    protected function getReflectionFunction(Closure $closure)
    {
        return new \ReflectionFunction($closure);
    }

    protected function getReflectionClass($alias)
    {
        return new \ReflectionClass($alias); 
    }
}
