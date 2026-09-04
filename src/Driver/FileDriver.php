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
        $compressData = $this->compress($key, $value, $expiration);

        if (file_put_contents($this->cacheDir.'/'.$filename, $compressData) !== false) {
            return true;
        }

        return false;
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

        if (!file_exists($this->cacheDir.'/'.$filename)) {
            return false;
        }

        $compressData = file_get_contents($this->cacheDir.'/'.$filename);
        return $this->decompress($compressData);
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
        return $this->deleteFilesInDir($this->cacheDir);
    }

    /**
     * @inheritDoc
     */
    public function isValid($value)
    {
        return is_array($value) && isset($value['key']) && array_key_exists('value', $value) && isset($value['expiration']);
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

            if (!$expiration instanceof Carbon) {
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
            $contents = file_get_contents($path);
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
