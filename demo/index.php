<?php
require_once __DIR__. '/../vendor/autoload.php';

function dd($d) {
    echo '<pre>';
    print_r($d);
    echo '</pre>';
}

$fileDriver = new \Roolith\Driver\FileDriver();
$pool = new \Roolith\Cache\Pool($fileDriver);
$item = $pool->getItem('foo');

dd($item);
