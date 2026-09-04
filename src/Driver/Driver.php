<?php
namespace Roolith\Caching\Driver;

use Carbon\Carbon;

abstract class Driver
{
    protected $config;

    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    public function setConfig(array $config = [])
    {
        $this->config = $config;

        return $this;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function compress($key, $value, Carbon $expiration)
    {
        return serialize([
            'key' => $key,
            'value' => $value,
            'expiration' => $expiration->toDateTimeString(),
        ]);
    }

    public function decompress($data)
    {
        $result = unserialize($data);
        $result['expiration'] = Carbon::parse($result['expiration']);

        return $result;
    }

    public function sanitizeKeyString($string)
    {
        $string = (string) $string;
        $prefix = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));

        if ($prefix === '') {
            $prefix = 'key';
        }

        $prefix = substr($prefix, 0, 32);
        $prefix = rtrim($prefix, '-');

        if ($prefix === '') {
            $prefix = 'key';
        }

        return $prefix.'-'.sha1($string);
    }
}
