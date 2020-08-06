<?php
namespace Roolith\Interfaces;


use Carbon\Carbon;

interface DriverInterface
{
    public function bootstrap();

    public function store($key, $value, Carbon $expiration);

    public function get($key);

    public function getRaw($key);

    public function has($key);

    public function delete($key);

    public function flush();

    public function isValid($value);

    public function isExpired($decompressData);
}
