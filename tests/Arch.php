<?php

declare(strict_types=1);

arch('source files use strict types')
    ->expect('League\Route')
    ->toUseStrictTypes();

arch('test files use strict types')
    ->expect('League\Route\Test')
    ->toUseStrictTypes();

arch('source does not depend on test fixtures')
    ->expect('League\Route')
    ->not->toUse('League\Route\Test');
