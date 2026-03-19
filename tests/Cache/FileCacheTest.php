<?php

declare(strict_types=1);

namespace League\Route\Cache;

use PHPUnit\Framework\TestCase;

class FileCacheTest extends TestCase
{
    public function testGetMultipleThrowsBadMethodCallException(): void
    {
        $cache = new FileCache('/tmp/test.cache', 86400);
        $this->expectException(\BadMethodCallException::class);
        $cache->getMultiple(['key1', 'key2']);
    }

    public function testSetMultipleThrowsBadMethodCallException(): void
    {
        $cache = new FileCache('/tmp/test.cache', 86400);
        $this->expectException(\BadMethodCallException::class);
        $cache->setMultiple(['key1' => 'val1']);
    }

    public function testDeleteMultipleThrowsBadMethodCallException(): void
    {
        $cache = new FileCache('/tmp/test.cache', 86400);
        $this->expectException(\BadMethodCallException::class);
        $cache->deleteMultiple(['key1']);
    }

    public function testGetReturnsContentWhenCacheFileExistsAndNotExpired(): void
    {
        $cacheFile = sys_get_temp_dir() . '/league_route_test_' . uniqid() . '.cache';
        $cache = new FileCache($cacheFile, 86400);

        $cache->set('key', 'cached-content');
        $this->assertSame('cached-content', $cache->get('key'));

        @unlink($cacheFile);
    }

    public function testGetReturnsDefaultWhenCacheFileDoesNotExist(): void
    {
        $cache = new FileCache('/tmp/non_existent_' . uniqid() . '.cache', 86400);
        $this->assertNull($cache->get('key'));
        $this->assertSame('default', $cache->get('key', 'default'));
    }

    public function testHasReturnsFalseWhenFileDoesNotExist(): void
    {
        $cache = new FileCache('/tmp/non_existent_' . uniqid() . '.cache', 86400);
        $this->assertFalse($cache->has('key'));
    }

    public function testHasReturnsTrueWhenFileExistsAndFresh(): void
    {
        $cacheFile = sys_get_temp_dir() . '/league_route_test_' . uniqid() . '.cache';
        $cache = new FileCache($cacheFile, 86400);

        $cache->set('key', 'content');
        $this->assertTrue($cache->has('key'));

        @unlink($cacheFile);
    }

    public function testHasReturnsFalseWhenFileExpired(): void
    {
        $cacheFile = sys_get_temp_dir() . '/league_route_test_' . uniqid() . '.cache';
        $cache = new FileCache($cacheFile, 0);

        file_put_contents($cacheFile, 'content');
        sleep(1);
        $this->assertFalse($cache->has('key'));

        @unlink($cacheFile);
    }

    public function testSetWritesContentToFile(): void
    {
        $cacheFile = sys_get_temp_dir() . '/league_route_test_' . uniqid() . '.cache';
        $cache = new FileCache($cacheFile, 86400);

        $result = $cache->set('key', 'some-content');
        $this->assertTrue($result);
        $this->assertFileExists($cacheFile);
        $this->assertSame('some-content', file_get_contents($cacheFile));

        @unlink($cacheFile);
    }

    public function testDeleteRemovesCacheFile(): void
    {
        $cacheFile = sys_get_temp_dir() . '/league_route_test_' . uniqid() . '.cache';
        file_put_contents($cacheFile, 'content');

        $cache = new FileCache($cacheFile, 86400);
        $cache->delete('key');

        $this->assertFileDoesNotExist($cacheFile);
    }

    public function testClearRemovesCacheFile(): void
    {
        $cacheFile = sys_get_temp_dir() . '/league_route_test_' . uniqid() . '.cache';
        file_put_contents($cacheFile, 'content');

        $cache = new FileCache($cacheFile, 86400);
        $cache->clear();

        $this->assertFileDoesNotExist($cacheFile);
    }
}
