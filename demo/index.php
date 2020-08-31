<?php
use Roolith\Caching\Cache\Cache;
use Roolith\Caching\Cache\CacheFactory;
use Roolith\Caching\Cache\SimpleCache;
use Roolith\Caching\Driver\FileDriver;
use Roolith\Caching\Cache\Pool;

require_once __DIR__. '/../vendor/autoload.php';

function dd($d) {
    echo '<pre>';
    print_r($d);
    echo '</pre>';
}

$fileDriver = new FileDriver(['dir' => __DIR__. '/cache']);
$pool = new Pool($fileDriver);
try {
    $item = $pool->getItem('foo');
} catch (\Psr\Cache\InvalidArgumentException $e) {
    echo $e->getMessage();
}

if (!$item->isHit()) {
    $item->set([1, 2, 3])->expiresAfter(3600);
    $pool->save($item);
}

dd($item->get());
dd($pool->getItemDetails('foo'));

$fileDriver = new FileDriver(['dir' => __DIR__. '/cache']);
$simpleCache = new SimpleCache(new FileDriver(['dir' => __DIR__. '/cache']));

try {
    dd($simpleCache->get('foo'));
} catch (\Psr\SimpleCache\InvalidArgumentException $e) {
    echo $e->getMessage();
}


$cache = new Cache();
$cache->driver('file', ['dir' => __DIR__. '/cache']);
dd($cache->get('foo'));

define('ROOLITH_CACHE_DIR', __DIR__. '/cache');
dd(CacheFactory::get('foo'));

CacheFactory::put('a', 'b', 3600);
dd(CacheFactory::get('a'));