<?php
namespace Roolith\Caching\Driver;


use Carbon\Carbon;
use Roolith\Caching\Interfaces\DriverInterface;
use Roolith\Caching\Traits\FileSystem;

class FileDriver extends Driver implements DriverInterface
{
    use FileSystem;

    public $cacheDir;

    /**
     * @inheritDoc
     */
    public function bootstrap()
    {
        $config = $this->getConfig();

        if (!isset($config['dir']) || !is_string($config['dir']) || trim($config['dir']) === '') {
            throw new \InvalidArgumentException('Cache directory is missing. Provide a "dir" config value.');
        }

        $this->cacheDir = $config['dir'];

        $this->makeDir($this->cacheDir);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function store($key, $value, Carbon $expiration)
    {
        $filename = $this->getFilenameByKey($key);
        $path = $this->cacheDir.'/'.$filename;
        $compressData = $this->compress($key, $value, $expiration);
        $tmpPath = $path.'.'.uniqid('', true).'.tmp';

        set_error_handler(function () {
            return true;
        });

        try {
            if (file_put_contents($tmpPath, $compressData, LOCK_EX) === false) {
                $this->unlinkQuietly($tmpPath);

                return false;
            }

            if (!rename($tmpPath, $path)) {
                $this->unlinkQuietly($tmpPath);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            $this->unlinkQuietly($tmpPath);

            return false;
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Unlink a temp file without emitting warnings.
     * Caller already holds a error handler, plus is_file guard avoids races.
     *
     * @param string $path
     * @return void
     */
    private function unlinkQuietly($path)
    {
        if (is_file($path)) {
            unlink($path);
        }
    }

    /**
     * @inheritDoc
     */
    public function get($key)
    {
        $filename = $this->getFilenameByKey($key);
        $path = $this->cacheDir.'/'.$filename;

        if (!file_exists($path)) {
            return false;
        }

        $compressData = $this->readCacheFile($path);

        if ($compressData === false) {
            return false;
        }

        $data = $this->safeDecompress($compressData);

        if (!$this->isValid($data) || $this->isExpired($data)) {
            return false;
        }

        return $data['value'];
    }

    /**
     * @inheritDoc
     */
    public function getRaw($key)
    {
        $filename = $this->getFilenameByKey($key);
        $path = $this->cacheDir.'/'.$filename;

        if (!file_exists($path)) {
            return false;
        }

        $compressData = $this->readCacheFile($path);

        if ($compressData === false) {
            return false;
        }

        return $this->safeDecompress($compressData);
    }

    /**
     * @inheritDoc
     */
    public function has($key)
    {
        $filename = $this->getFilenameByKey($key);
        $path = $this->cacheDir.'/'.$filename;

        if (!file_exists($path)) {
            return false;
        }

        $compressData = $this->readCacheFile($path);

        if ($compressData === false) {
            return false;
        }

        $data = $this->safeDecompress($compressData);

        return $this->isValid($data) && !$this->isExpired($data);
    }

    /**
     * @inheritDoc
     */
    public function delete($key)
    {
        if (!$this->has($key)) {
            return false;
        }

        $filename = $this->getFilenameByKey($key);

        return unlink($this->cacheDir.'/'.$filename);
    }

    /**
     * @inheritDoc
     */
    public function flush()
    {
        return $this->deleteFilesInDir($this->cacheDir, '*.'.$this->getCacheFileExtension());
    }

    /**
     * @inheritDoc
     */
    public function isValid($value)
    {
        if (!is_array($value)) {
            return false;
        }

        if (!isset($value['key']) || !is_string($value['key'])) {
            return false;
        }

        if (!array_key_exists('value', $value)) {
            return false;
        }

        if ($this->containsObject($value['value'])) {
            return false;
        }

        if (!array_key_exists('expiration', $value) || !isset($value['expiration'])) {
            return false;
        }

        $expiration = $value['expiration'];

        if ($expiration instanceof Carbon || $expiration instanceof \DateTimeInterface) {
            return true;
        }

        if (is_int($expiration)) {
            return true;
        }

        if (is_string($expiration) && trim($expiration) !== '') {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function isExpired($decompressData)
    {
        if (!is_array($decompressData) || !isset($decompressData['expiration'])) {
            return true;
        }

        try {
            $expiration = $decompressData['expiration'];

            if ($expiration instanceof Carbon) {
                return Carbon::now()->gte($expiration);
            }

            if ($expiration instanceof \DateTimeInterface) {
                return Carbon::now()->gte(Carbon::instance($expiration));
            }

            if (is_int($expiration) || (is_string($expiration) && trim($expiration) !== '' && ctype_digit(trim($expiration)))) {
                $expiration = Carbon::createFromTimestamp((int) $expiration);
            } else {
                $expiration = Carbon::parse($expiration);
            }

            return Carbon::now()->gte($expiration);
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * Read cache file without emitting warnings.
     *
     * Uses a shared lock so concurrent atomic writes stay consistent.
     *
     * @param string $path
     * @return string|false
     */
    private function readCacheFile($path)
    {
        if (!is_file($path) || !is_readable($path)) {
            return false;
        }

        set_error_handler(function () {
            return true;
        });

        try {
            $handle = fopen($path, 'rb');

            if ($handle === false) {
                return false;
            }

            try {
                if (!flock($handle, LOCK_SH)) {
                    return false;
                }

                $contents = stream_get_contents($handle);
                flock($handle, LOCK_UN);
            } finally {
                fclose($handle);
            }
        } finally {
            restore_error_handler();
        }

        return $contents;
    }

    /**
     * Decompress payload without emitting warnings.
     *
     * @param mixed $compressData
     * @return mixed
     */
    private function safeDecompress($compressData)
    {
        if (!is_string($compressData)) {
            return false;
        }

        set_error_handler(function () {
            return true;
        });

        try {
            return $this->decompress($compressData);
        } catch (\Throwable $e) {
            return false;
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Get cache file extension
     *
     * @return string
     */
    protected function getCacheFileExtension()
    {
        $config = $this->getConfig();

        if (isset($config['ext']) && is_string($config['ext'])) {
            $ext = trim($config['ext']);

            if ($ext !== '' && preg_match('/^[A-Za-z0-9]+$/', $ext)) {
                return $ext;
            }
        }

        return 'rcache';
    }

    /**
     * Get cache file name by key
     *
     * @param $key
     * @return string
     */
    protected function getFilenameByKey($key)
    {
        return $this->sanitizeKeyString($key).'.'.$this->getCacheFileExtension();
    }
}
