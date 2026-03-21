<?php

declare(strict_types=1);

namespace League\Route;

interface UrlGeneratorInterface
{
    /** @param array<string, string> $substitutions */
    public function generateUrl(string $name, array $substitutions = []): string;
}
