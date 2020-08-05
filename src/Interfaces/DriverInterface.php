<?php
namespace Roolith\Interfaces;


use Carbon\Carbon;

interface DriverInterface
{
    public function store($key, $value, Carbon $expiration);

    public function storeMany(array $array);

    public function get($key);

    public function many(array $keys);

    public function has($key);

    public function delete($key);

    public function deleteMany(array $keys);

    public function flush();

    public function isValid($value);

    public function isExpired($key);
}
