<?php

use Roolith\Cache\Cache;
use Roolith\Cache\CacheFactory;
use Roolith\Cache\SimpleCache;
use Roolith\Driver\FileDriver;
use Roolith\Cache\Pool;

require_once __DIR__. '/../vendor/autoload.php';

function dd($d) {
    echo '<pre>';
    print_r($d);
    echo '</pre>';
}

$fileDriver = new FileDriver(['dir' => __DIR__. '/cache']);
$pool = new Pool($fileDriver);
$item = $pool->getItem('foo');

if (!$item->isHit()) {
    $item->set([1, 2, 3])->expiresAfter(3600);
    $pool->save($item);
}

dd($item->get());
dd($pool->getItemDetails('foo'));

$fileDriver = new FileDriver(['dir' => __DIR__. '/cache']);
$simpleCache = new SimpleCache($fileDriver);

dd($simpleCache->get('foo'));


$cache = new Cache();
$cache->driver('file', ['dir' => __DIR__. '/cache']);
dd($cache->get('foo'));

define('ROOLITH_CACHE_DIR', __DIR__. '/cache');
dd(CacheFactory::get('foo'));

CacheFactory::put('a', 'b', 3600);
dd(CacheFactory::get('a'));
