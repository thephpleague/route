<?php

declare(strict_types=1);

namespace League\Route\Cache;

use Psr\SimpleCache\{CacheInterface, InvalidArgumentException};

class FileCache implements CacheInterface
{
    public function __construct(protected string $cacheFilePath, protected int $ttl)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return ($this->has($key)) ? file_get_contents($this->cacheFilePath) : $default;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        return (bool) file_put_contents($this->cacheFilePath, $value);
    }

    public function has(string $key): bool
    {
        return file_exists($this->cacheFilePath) && time() - filemtime($this->cacheFilePath) < $this->ttl;
    }

    public function delete(string $key): bool
    {
        return unlink($this->cacheFilePath);
    }

    public function clear(): bool
    {
        return $this->delete($this->cacheFilePath);
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        throw new \BadMethodCallException('FileCache does not support multi-key operations');
    }

    /**
     * @param iterable<mixed> $values
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        throw new \BadMethodCallException('FileCache does not support multi-key operations');
    }

    public function deleteMultiple(iterable $keys): bool
    {
        throw new \BadMethodCallException('FileCache does not support multi-key operations');
    }
}
