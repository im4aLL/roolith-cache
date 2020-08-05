<?php
namespace Roolith\Driver;


use Carbon\Carbon;
use Roolith\Interfaces\DriverInterface;

class FileDriver extends Driver implements DriverInterface
{

    public function store($key, $value, Carbon $expiration)
    {
        // TODO: Implement store() method.
    }

    public function storeMany(array $array)
    {
        // TODO: Implement storeMany() method.
    }

    public function get($key)
    {
        // TODO: Implement get() method.
    }

    public function many(array $keys)
    {
        // TODO: Implement many() method.
    }

    public function has($key)
    {
        // TODO: Implement has() method.
    }

    public function delete($key)
    {
        // TODO: Implement delete() method.
    }

    public function deleteMany(array $keys)
    {
        // TODO: Implement deleteMany() method.
    }

    public function flush()
    {
        // TODO: Implement flush() method.
    }

    public function isValid($value)
    {
        // TODO: Implement isValid() method.
    }

    public function isExpired($key)
    {
        // TODO: Implement isExpired() method.
    }
}
