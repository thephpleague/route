<?php

declare(strict_types=1);

namespace League\Route;

use LogicException;

final class MatchResult
{
    /** @param array<string> $allowedMethods */
    private function __construct(
        private readonly MatchStatus $status,
        private readonly ?Route $route,
        private readonly array $allowedMethods,
    ) {}

    public static function found(Route $route): self
    {
        return new self(MatchStatus::Found, $route, []);
    }

    public static function notFound(): self
    {
        return new self(MatchStatus::NotFound, null, []);
    }

    /** @param array<string> $allowedMethods */
    public static function methodNotAllowed(array $allowedMethods): self
    {
        return new self(MatchStatus::MethodNotAllowed, null, $allowedMethods);
    }

    public function isFound(): bool
    {
        return $this->status === MatchStatus::Found;
    }

    public function isMethodNotAllowed(): bool
    {
        return $this->status === MatchStatus::MethodNotAllowed;
    }

    public function getRoute(): Route
    {
        if ($this->route === null) {
            throw new LogicException('No route available for a non-matched result');
        }
        return $this->route;
    }

    /** @return array<string> */
    public function getAllowedMethods(): array
    {
        if ($this->status !== MatchStatus::MethodNotAllowed) {
            throw new LogicException('Allowed methods are only available for method-not-allowed results');
        }
        return $this->allowedMethods;
    }

    public function getStatus(): MatchStatus
    {
        return $this->status;
    }
}
