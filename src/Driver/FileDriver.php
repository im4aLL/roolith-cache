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

        if (!file_exists($this->cacheDir.'/'.$filename)) {
            return false;
        }

        $compressData = file_get_contents($this->cacheDir.'/'.$filename);
        $data = $this->decompress($compressData);

        if ($this->isExpired($data) || !$this->isValid($data)) {
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

        return file_exists($this->cacheDir.'/'.$filename);
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
        return is_array($value) && isset($value['key']) && isset($value['value']) && isset($value['expiration']);
    }

    /**
     * @inheritDoc
     */
    public function isExpired($decompressData)
    {
        return Carbon::now()->gte($decompressData['expiration']);
    }

    /**
     * Get cache file extension
     *
     * @return string
     */
    protected function getCacheFileExtension()
    {
        $config = $this->getConfig();

        return isset($config['ext']) ? $config['ext'] : 'rcache';
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
