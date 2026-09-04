# roolith-cache
[PSR-6](http://www.php-fig.org/psr/psr-6/) and [PSR-16](http://www.php-fig.org/psr/psr-16/) compatible cache system using PHP.
Only the file driver is available.
Any unknown driver name falls back to the file driver.

## Install

```
composer require roolith/cache
```

## Requirements

- PHP ^8.0.
- `psr/cache` ^1.0 or ^2.0 or ^3.0.
- `psr/simple-cache` ^1.0 or ^2.0 or ^3.0.
- `nesbot/carbon` ^2.0 or ^3.0.

## Bootstrap cache directory

Define `ROOLITH_CACHE_DIR` before `vendor/autoload.php` is loaded and before the first `CacheFactory` call.
This order keeps the constant visible to the factory resolver at runtime.
If you cannot define a constant early, set `CacheFactory::$fileDriverCacheDir` before use or pass an explicit `['dir' => ...]` config.
When no dir is configured, the factory falls back to `sys_get_temp_dir() . '/roolith-cache'`.
`FileDriver` itself is strict.
Calling `bootstrap()` without a non-empty string `dir` throws `InvalidArgumentException`.

```php
<?php
define('ROOLITH_CACHE_DIR', __DIR__ . '/cache');

require __DIR__ . '/vendor/autoload.php';

use Roolith\Caching\Cache\CacheFactory;

CacheFactory::put('a', 'b', 3600);
echo CacheFactory::get('a');
```

Explicit config always wins over the constant and over the static override.
The static override wins over the constant and over the temp fallback.

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use Roolith\Caching\Cache\CacheFactory;

// Highest priority: explicit dir.
CacheFactory::driver('file', ['dir' => __DIR__ . '/cache']);

// Middle priority: static override.
CacheFactory::$fileDriverCacheDir = __DIR__ . '/cache';

// Lowest priority: ROOLITH_CACHE_DIR constant or temp fallback.
```

## Usage

You may choose any method.
All `dir` values must be non-empty strings, otherwise `FileDriver::bootstrap()` throws `InvalidArgumentException`.

### Method 1: Factory

```php
<?php
define('ROOLITH_CACHE_DIR', __DIR__ . '/cache');

require __DIR__ . '/vendor/autoload.php';

use Roolith\Caching\Cache\CacheFactory;

// Will save cache with 1-hour TTL.
CacheFactory::put('a', 'b', 3600);

// Will retrieve cache or false when missing, expired, or corrupt.
CacheFactory::get('a');

// You can select driver and store.
CacheFactory::driver('file')->put('a', 'b', 3600);

// Will return boolean.
CacheFactory::has('foo');

// Will delete cache item, false when the key has no valid entry.
CacheFactory::remove('foo');

// Will delete all `*.rcache` items in the configured dir.
CacheFactory::flush();
```

### Method 2: Cache

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use Roolith\Caching\Cache\Cache;

$cache = new Cache();
$cache->driver('file', ['dir' => __DIR__ . '/cache']);

// Third argument is seconds and defaults to 3600.
$cache->put('foo', 'bar', 3600);
print_r($cache->get('foo'));
```

### Method 3: PSR-6

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use Roolith\Caching\Driver\FileDriver;
use Roolith\Caching\Cache\Pool;

$fileDriver = new FileDriver(['dir' => __DIR__ . '/cache']);
$pool = new Pool($fileDriver);
$item = $pool->getItem('foo');

if (!$item->isHit()) {
    $item->set([1, 2, 3])->expiresAfter(3600);
    $pool->save($item);
}

print_r($item->get());
```

`Pool::getItems()` preserves input keys in the returned array.
`Pool::save()` without an explicit expiration falls back to the item default, which is one month from creation.

### Method 4: PSR-16

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use Roolith\Caching\Cache\SimpleCache;
use Roolith\Caching\Driver\FileDriver;

$fileDriver = new FileDriver(['dir' => __DIR__ . '/cache']);
$simpleCache = new SimpleCache($fileDriver);

$simpleCache->set('foo', 'bar', 3600);
print_r($simpleCache->get('foo'));
```

`SimpleCache::getMultiple()` preserves input keys in the returned array.
Bulk methods accept arrays or `Traversable` and validate every key.
Invalid keys throw PSR-16 `InvalidArgumentException`.

## TTL semantics

`Cache::put($key, $value, $expireAfter = 3600)` takes seconds and defaults to one hour.
`Item::expiresAfter()` accepts an integer in seconds, a `DateInterval` as a total duration via `Carbon::now()->add()`, or null for the item default.
`Item::expiresAt()` accepts a `DateTimeInterface` or null for the item default.
`Item` defaults to one month from construction, and `Pool::save()` re-applies that default when expiration is null.
`SimpleCache::set()` uses a 5-hour default when TTL is null (`SimpleCache::DEFAULT_TTL_HOURS`).
An integer TTL is seconds, a `DateInterval` TTL is added as a total duration, and zero or negative integers expire immediately.
Any other TTL type such as string, float, bool, array, or object throws PSR-16 `InvalidArgumentException`.
`setMultiple()` validates TTL before writing so an invalid TTL fails without partial writes.

```php
<?php
use Roolith\Caching\Cache\SimpleCache;
use Roolith\Caching\Driver\FileDriver;

$cache = new SimpleCache(new FileDriver(['dir' => __DIR__ . '/cache']));

$cache->set('null-ttl', 'v', null); // Expires in 5 hours.
$cache->set('seconds', 'v', 3600); // Expires in 1 hour.
$cache->set('interval', 'v', new DateInterval('P1D')); // Expires in 1 full day.
$cache->set('gone', 'v', 0); // Immediately expired.
```

## Hits, falsy values, and expiration

`Item::isHit()` returns an explicit hit flag, not value truthiness.
Falsy values `0`, `false`, `null`, `[]`, and `''` round-trip with `isHit() === true` while unexpired.
Missing, expired, corrupt, tampered, empty, or object-payload entries return `isHit() === false` for PSR-6 and `false` or `$default` for PSR-16 and `FileDriver::get()`.
`FileDriver::has()` checks both payload validity and expiration.
`FileDriver::get()` checks validity before expiration and returns `false` for invalid or expired payloads.
There is no lingering falsy-value caveat.
If you see a miss for a falsy value, the entry is actually missing or expired.

## Storage details

Keys are mapped to `safe-prefix + '-' + sha1(full-key) + '.' + ext`.
The prefix keeps the first 32 safe characters for readability, while the SHA1 prevents `foo/bar` versus `foo-bar` versus `FOO-BAR` collisions.
The `ext` config is whitelisted to alphanumeric only and falls back to `rcache`.
Payloads store expiration as a Unix timestamp and use `unserialize()` with `allowed_classes => false`.
Tampered, empty, object-payload, or bad-shape payloads fail validation and read as miss.
Writes are atomic via temp file plus `LOCK_EX` plus `rename()`, and reads use a shared lock.
`flush()` only deletes `*.<ext>` files, skips dot entries and directories, and checks `is_file()` before `unlink()`.

## Drivers

Only the file driver is implemented.
`Cache::driver()` switches on `'file'` with a `default` branch, so unknown names currently fall back to `FileDriver` instead of throwing.
Pass `['dir' => $dir]` and optionally `['ext' => $ext]` when constructing `FileDriver` directly.

## Development

```text
./vendor/bin/phpunit --testdox tests
```
