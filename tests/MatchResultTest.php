<?php

declare(strict_types=1);

namespace League\Route;

use PHPUnit\Framework\TestCase;

class MatchResultTest extends TestCase
{
    public function testFoundResult(): void
    {
        $route = new Route('GET', '/test', static function () {});
        $result = MatchResult::found($route);

        $this->assertTrue($result->isFound());
        $this->assertFalse($result->isMethodNotAllowed());
        $this->assertSame($route, $result->getRoute());
        $this->assertSame(MatchStatus::Found, $result->getStatus());
    }

    public function testNotFoundResult(): void
    {
        $result = MatchResult::notFound();

        $this->assertFalse($result->isFound());
        $this->assertFalse($result->isMethodNotAllowed());
        $this->assertSame(MatchStatus::NotFound, $result->getStatus());
    }

    public function testMethodNotAllowedResult(): void
    {
        $result = MatchResult::methodNotAllowed(['GET', 'POST']);

        $this->assertFalse($result->isFound());
        $this->assertTrue($result->isMethodNotAllowed());
        $this->assertSame(['GET', 'POST'], $result->getAllowedMethods());
        $this->assertSame(MatchStatus::MethodNotAllowed, $result->getStatus());
    }

    public function testGetRouteThrowsOnNotFound(): void
    {
        $this->expectException(\LogicException::class);
        MatchResult::notFound()->getRoute();
    }

    public function testGetAllowedMethodsThrowsOnFound(): void
    {
        $this->expectException(\LogicException::class);
        $route = new Route('GET', '/test', static function () {});
        MatchResult::found($route)->getAllowedMethods();
    }
}
