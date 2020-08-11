# roolith-cache
[PSR-6](http://www.php-fig.org/psr/psr-6/) and [PSR-16](http://www.php-fig.org/psr/psr-16/) compatible cache system using PHP

## Install

```
composer require roolith/cache
```

## Usage
You may choose any method

### Method 1: Factory
```php
<?php
define('ROOLITH_CACHE_DIR', __DIR__. '/cache');

use Roolith\Cache\CacheFactory;

// will save cache
CacheFactory::put('a', 'b', 3600);

// will retrive cache
CacheFactory::get('a');

// you can select driver and store
CacheFactory::driver('file')->put('a', 'b', 3600);

// will return boolean
CacheFactory::has('foo'); 

// will delete cache item
CacheFactory::remove('foo');

// will delete all cache item
CacheFactory::flush();
```

### Method 2: Cache
```php
<?php
use Roolith\Cache\Cache;

$cache = new Cache();
$cache->driver('file', ['dir' => __DIR__. '/cache']);

print_r($cache->get('foo'));
```

### Method 3: PSR-6
```php
<?php
use Roolith\Driver\FileDriver;
use Roolith\Cache\Pool;

$fileDriver = new FileDriver(['dir' => __DIR__. '/cache']);
$pool = new Pool($fileDriver);
$item = $pool->getItem('foo');

if (!$item->isHit()) {
    $item->set([1, 2, 3])->expiresAfter(3600);
    $pool->save($item);
}

print_r($item->get());
```

### Method 4: PSR-16
```php
<?php
use Roolith\Cache\SimpleCache;
use Roolith\Driver\FileDriver;

$fileDriver = new FileDriver(['dir' => __DIR__. '/cache']);
$simpleCache = new SimpleCache($fileDriver);

print_r($simpleCache->get('foo'));
```

Note: Only file based driver added. Have plan to add more driver later.


#### Development
```text
$ ./vendor/bin/phpunit --testdox tests
PHPUnit 9.2.6 by Sebastian Bergmann and contributors.

Cache Factory
 ✔ Should store cache
 ✔ Should check whether has cache
 ✔ Should get cache
 ✔ Should delete cache
 ✔ Should delete all cache

Cache
 ✔ Should store cache
 ✔ Should check whether has cache
 ✔ Should get cache
 ✔ Should delete cache
 ✔ Should delete all cache

File Driver
 ✔ Should be an instance of driver
 ✔ Should implement driver interface
 ✔ Should add config
 ✔ Should create cache dir
 ✔ Should store cache
 ✔ Should get cached item
 ✔ Should get raw cached item
 ✔ Should return boolean whether cache exists or not
 ✔ Should delete cache item
 ✔ Should delete all cached item
 ✔ Should return false for expired cache item

Item
 ✔ Should get key
 ✔ Should get value
 ✔ Should return boolean whether value is hit
 ✔ Should set value
 ✔ Should set expire at
 ✔ Should set expire after

Pool
 ✔ Should get item
 ✔ Should get multiple items
 ✔ Should check whether has item
 ✔ Should clear all cache
 ✔ Should delete item
 ✔ Should delete multiple items
 ✔ Should save cache item
 ✔ Should save multiple items via commit
 ✔ Should have raw details for cache item
 ✔ Should give invalid argument exception for invalid key

Simple Cache
 ✔ Should get cache item
 ✔ Should store cache item
 ✔ Should delete cache item
 ✔ Should delete all cache item
 ✔ Should get multiple cache item
 ✔ Should store multiple items
 ✔ Should delete multiple items
 ✔ Should check whether has cache item
 ✔ Should give invalid argument exception

Time: 00:00.118, Memory: 8.00 MB

OK (46 tests, 69 assertions)
```
