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
