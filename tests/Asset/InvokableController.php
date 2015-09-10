<?php

namespace League\Route\Test\Asset;

class InvokableController
{
    public function __invoke()
    {
        return true;
    }
}
