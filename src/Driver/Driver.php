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
            'expiration' => $expiration->getTimestamp(),
        ]);
    }

    public function decompress($data)
    {
        if (!is_string($data) || $data === '') {
            return false;
        }

        set_error_handler(function () {
            return true;
        });

        try {
            $result = unserialize($data, ['allowed_classes' => false]);
        } catch (\Throwable $e) {
            return false;
        } finally {
            restore_error_handler();
        }

        if (!is_array($result)) {
            return false;
        }

        if (!isset($result['key']) || !is_string($result['key'])) {
            return false;
        }

        if (!array_key_exists('value', $result)) {
            return false;
        }

        if (!array_key_exists('expiration', $result)) {
            return false;
        }

        if ($this->containsObject($result['value'])) {
            return false;
        }

        $expiration = $this->parseExpiration($result['expiration']);

        if ($expiration === false) {
            return false;
        }

        $result['expiration'] = $expiration;

        return $result;
    }

    protected function containsObject($value)
    {
        if (is_object($value)) {
            return true;
        }

        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if ($this->containsObject($item)) {
                return true;
            }
        }

        return false;
    }

    protected function parseExpiration($expiration)
    {
        if ($expiration instanceof Carbon) {
            return $expiration;
        }

        if ($expiration instanceof \DateTimeInterface) {
            return Carbon::instance($expiration);
        }

        if (is_int($expiration)) {
            try {
                return Carbon::createFromTimestamp($expiration);
            } catch (\Throwable $e) {
                return false;
            }
        }

        if (is_string($expiration) && trim($expiration) !== '') {
            $trimmed = trim($expiration);

            if (ctype_digit($trimmed)) {
                try {
                    return Carbon::createFromTimestamp((int) $trimmed);
                } catch (\Throwable $e) {
                    return false;
                }
            }

            try {
                return Carbon::parse($expiration);
            } catch (\Throwable $e) {
                return false;
            }
        }

        return false;
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
