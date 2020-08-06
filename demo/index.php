<?php
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
$simpleCache = new \Roolith\Cache\SimpleCache($fileDriver);

dd($simpleCache->get('foo'));
