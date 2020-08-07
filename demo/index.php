<?php
require_once __DIR__. '/../vendor/autoload.php';

function dd($d) {
    echo '<pre>';
    print_r($d);
    echo '</pre>';
}

$fileDriver = new \Roolith\Driver\FileDriver(['dir' => __DIR__. '/cache']);
$pool = new \Roolith\Cache\Pool($fileDriver);
$item = $pool->getItem('foo');

if (!$item->isHit()) {
    $item->set([1, 2, 3])->expiresAfter(3600);
    $pool->save($item);
}

dd($item->get());
dd($pool->getItemDetails('foo'));

$fileDriver = new \Roolith\Driver\FileDriver(['dir' => __DIR__. '/cache']);
$simpleCache = new \Roolith\Cache\SimpleCache($fileDriver);

dd($simpleCache->get('foo'));


$cache = new \Roolith\Cache\Cache();
$cache->driver('file', ['dir' => __DIR__. '/cache']);
dd($cache->get('foo'));

define('ROOLITH_CACHE_DIR', __DIR__. '/cache');
dd(\Roolith\Cache\CacheFactory::get('foo'));

\Roolith\Cache\CacheFactory::put('a', 'b', 3600);
dd(\Roolith\Cache\CacheFactory::get('a'));
