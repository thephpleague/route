<?php

declare(strict_types=1);

namespace League\Route\Cache;

use Psr\SimpleCache\CacheInterface;

class FileCache implements CacheInterface
{
    public function __construct(protected string $cacheFilePath, protected int $ttl)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return ($this->has($key)) ? file_get_contents($this->cacheFilePath) : $default;
    }

    public function set($key, $value, $ttl = null): bool
    {
        return (bool) file_put_contents($this->cacheFilePath, $value);
    }

    public function has($key): bool
    {
        return file_exists($this->cacheFilePath) && time() - filemtime($this->cacheFilePath) < $this->ttl;
    }

    public function delete($key): bool
    {
        return unlink($this->cacheFilePath);
    }

    public function clear(): bool
    {
        return $this->delete($this->cacheFilePath);
    }

    public function getMultiple($keys, $default = null): iterable
    {
        return [];
    }

    public function setMultiple($values, $ttl = null): bool
    {
        return false;
    }

    public function deleteMultiple($keys): bool
    {
        return false;
    }
}
