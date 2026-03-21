<?php

declare(strict_types=1);

use League\Route\Cache\FileCache;

test('getMultiple throws BadMethodCallException', function () {
    $cache = new FileCache('/tmp/test.cache', 86400);
    expect(fn() => $cache->getMultiple(['key1', 'key2']))->toThrow(BadMethodCallException::class);
});

test('setMultiple throws BadMethodCallException', function () {
    $cache = new FileCache('/tmp/test.cache', 86400);
    expect(fn() => $cache->setMultiple(['key1' => 'val1']))->toThrow(BadMethodCallException::class);
});

test('deleteMultiple throws BadMethodCallException', function () {
    $cache = new FileCache('/tmp/test.cache', 86400);
    expect(fn() => $cache->deleteMultiple(['key1']))->toThrow(BadMethodCallException::class);
});

test('get returns cached content when file exists and has not expired', function () {
    $cacheFile = sys_get_temp_dir() . '/league_route_test_' . uniqid() . '.cache';
    $cache = new FileCache($cacheFile, 86400);

    $cache->set('key', 'cached-content');

    expect($cache->get('key'))->toBe('cached-content');

    @unlink($cacheFile);
});

test('get returns null when cache file does not exist', function () {
    $cache = new FileCache('/tmp/non_existent_' . uniqid() . '.cache', 86400);

    expect($cache->get('key'))->toBeNull();
});

test('get returns provided default when cache file does not exist', function () {
    $cache = new FileCache('/tmp/non_existent_' . uniqid() . '.cache', 86400);

    expect($cache->get('key', 'default'))->toBe('default');
});

test('has returns false when cache file does not exist', function () {
    $cache = new FileCache('/tmp/non_existent_' . uniqid() . '.cache', 86400);

    expect($cache->has('key'))->toBeFalse();
});

test('has returns true when cache file exists and is fresh', function () {
    $cacheFile = sys_get_temp_dir() . '/league_route_test_' . uniqid() . '.cache';
    $cache = new FileCache($cacheFile, 86400);

    $cache->set('key', 'content');

    expect($cache->has('key'))->toBeTrue();

    @unlink($cacheFile);
});

test('has returns false when cache file has expired', function () {
    $cacheFile = sys_get_temp_dir() . '/league_route_test_' . uniqid() . '.cache';
    $cache = new FileCache($cacheFile, 0);

    file_put_contents($cacheFile, 'content');
    sleep(1);

    expect($cache->has('key'))->toBeFalse();

    @unlink($cacheFile);
});

test('set writes content to cache file and returns true', function () {
    $cacheFile = sys_get_temp_dir() . '/league_route_test_' . uniqid() . '.cache';
    $cache = new FileCache($cacheFile, 86400);

    $result = $cache->set('key', 'some-content');

    expect($result)->toBeTrue();
    expect($cacheFile)->toBeFile();
    expect(file_get_contents($cacheFile))->toBe('some-content');

    @unlink($cacheFile);
});

test('delete removes the cache file', function () {
    $cacheFile = sys_get_temp_dir() . '/league_route_test_' . uniqid() . '.cache';
    file_put_contents($cacheFile, 'content');

    $cache = new FileCache($cacheFile, 86400);
    $cache->delete('key');

    expect($cacheFile)->not->toBeFile();
});

test('clear removes the cache file', function () {
    $cacheFile = sys_get_temp_dir() . '/league_route_test_' . uniqid() . '.cache';
    file_put_contents($cacheFile, 'content');

    $cache = new FileCache($cacheFile, 86400);
    $cache->clear();

    expect($cacheFile)->not->toBeFile();
});
