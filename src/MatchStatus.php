<?php

declare(strict_types=1);

namespace League\Route;

enum MatchStatus
{
    case Found;
    case NotFound;
    case MethodNotAllowed;
}
